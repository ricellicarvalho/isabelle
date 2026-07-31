<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Receivable;
use App\Services\Contracts\ContractRenewalService;
use Illuminate\Validation\ValidationException;

class ContractObserver
{
    public function updating(Contract $contract): void
    {
        if ($contract->getOriginal('status') === 'rascunho') {
            return;
        }

        $changedVersionedFields = array_intersect(
            array_keys($contract->getDirty()),
            ContractRenewalService::VERSIONED_FIELDS,
        );

        if ($changedVersionedFields !== []) {
            throw ValidationException::withMessages([
                'contract' => 'Dados de um contrato vigente não podem ser sobrescritos. Use a ação “Renovar contrato” para preservar o histórico.',
            ]);
        }
    }

    /**
     * RN01 + RN10.1: Geração automática de parcelas em receivables
     * sempre que um Contrato é criado (independente do status), exceto
     * quando já nascer cancelado.
     */
    public function created(Contract $contract): void
    {
        $version = $contract->versions()->create([
            'version_number' => 1,
            'change_type' => 'original',
            'status' => match ($contract->status) {
                'rascunho' => 'draft',
                'cancelado' => 'cancelled',
                default => 'active',
            },
            'client_id' => $contract->client_id,
            'category_id' => $contract->category_id,
            'numero' => $contract->numero,
            'tipo_servico' => $contract->tipo_servico,
            'descricao' => $contract->descricao,
            'valor_total' => $contract->valor_total,
            'forma_pagamento' => $contract->forma_pagamento,
            'quantidade_parcelas' => $contract->quantidade_parcelas,
            'data_inicio' => $contract->data_inicio,
            'data_fim' => $contract->data_fim,
            'arquivo_pdf' => $contract->arquivo_pdf,
            'observacoes' => $contract->observacoes,
            'change_reason' => 'Criação do contrato.',
            'activated_at' => now(),
            'created_by' => $contract->created_by,
            'activated_by' => $contract->created_by,
        ]);

        $contract->updateQuietly(['current_version_id' => $version->id]);

        if ($contract->status !== 'cancelado') {
            $this->generateReceivables($contract, $version);
        }
    }

    /**
     * RN10.1: Geração automática quando o Contrato é alterado para 'ativo'
     * (ex: rascunho -> ativo).
     *
     * RN10.2: Estorno automático quando o Contrato é cancelado.
     */
    public function updated(Contract $contract): void
    {
        if ($contract->getOriginal('status') === 'rascunho' && $contract->currentVersion) {
            $contract->currentVersion->update($contract->only(ContractRenewalService::VERSIONED_FIELDS));
        }

        if (! $contract->wasChanged('status')) {
            return;
        }

        $previousStatus = $contract->getOriginal('status');
        $newStatus = $contract->status;

        // Ativação: gera receivables se ainda não existirem
        if ($newStatus === 'ativo' && $previousStatus !== 'ativo') {
            $contract->currentVersion?->update(['status' => 'active', 'activated_at' => now(), 'activated_by' => auth()->id()]);
            if ($contract->receivables()->count() === 0) {
                $this->generateReceivables($contract);
            }
        }

        // RN10.2: Cancelamento - cancela parcelas pendentes
        if ($newStatus === 'cancelado') {
            $contract->currentVersion?->update(['status' => 'cancelled']);
            $contract->receivables()
                ->where('status', 'pendente')
                ->update([
                    'status' => 'cancelado',
                    'deleted_by' => auth()->id(),
                ]);
        }
    }

    /**
     * RN10.3: Bloqueio de exclusão de Contratos com parcelas pagas.
     */
    public function deleting(Contract $contract): void
    {
        $hasPaidReceivables = $contract->receivables()
            ->where('status', 'pago')
            ->exists();

        if ($hasPaidReceivables) {
            throw ValidationException::withMessages([
                'contract' => 'Não é possível excluir um contrato com parcelas já pagas. Realize o estorno manual antes de excluir.',
            ]);
        }
    }

    /**
     * Cria as parcelas de receivables baseado nos dados do contrato.
     * Público para permitir uso em Bulk Actions (RN04).
     */
    public function generateReceivables(Contract $contract, ?ContractVersion $version = null): void
    {
        $version ??= $contract->currentVersion;
        $quantidade = max(1, (int) $contract->quantidade_parcelas);
        $valorParcela = round($contract->valor_total / $quantidade, 2);
        // Ajusta a última parcela para corrigir arredondamentos
        $somaParciais = $valorParcela * ($quantidade - 1);
        $valorUltimaParcela = round($contract->valor_total - $somaParciais, 2);

        $dataInicio = $contract->data_inicio->copy();

        for ($i = 1; $i <= $quantidade; $i++) {
            Receivable::create([
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'contract_version_id' => $version?->id,
                'category_id' => $contract->category_id,
                'descricao' => "Parcela {$i}/{$quantidade} - Contrato {$contract->numero}",
                'valor' => $i === $quantidade ? $valorUltimaParcela : $valorParcela,
                'data_vencimento' => $dataInicio->copy()->addMonths($i - 1),
                'forma_pagamento' => $contract->forma_pagamento,
                'numero_parcela' => $i,
                'status' => 'pendente',
                'created_by' => $contract->created_by,
            ]);
        }
    }
}
