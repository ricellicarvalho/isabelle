<?php

namespace App\Observers;

use App\Models\Contract;
use App\Models\Receivable;
use Illuminate\Validation\ValidationException;

class ContractObserver
{
    /**
     * RN01 + RN10.1: Geração automática de parcelas em receivables
     * quando um Contrato é criado já com status 'ativo'.
     */
    public function created(Contract $contract): void
    {
        if ($contract->status === 'ativo') {
            $this->generateReceivables($contract);
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
        if (! $contract->wasChanged('status')) {
            return;
        }

        $previousStatus = $contract->getOriginal('status');
        $newStatus = $contract->status;

        // Ativação: gera receivables se ainda não existirem
        if ($newStatus === 'ativo' && $previousStatus !== 'ativo') {
            if ($contract->receivables()->count() === 0) {
                $this->generateReceivables($contract);
            }
        }

        // RN10.2: Cancelamento - cancela parcelas pendentes
        if ($newStatus === 'cancelado') {
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
    public function generateReceivables(Contract $contract): void
    {
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
