<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class BoletoBatchService
{
    public function validationMessage(Collection $selected): ?string
    {
        try {
            $ids = $selected->pluck('id')->filter()->unique()->values();

            if ($ids->isEmpty()) {
                throw new RuntimeException('Selecione ao menos uma parcela.');
            }

            $receivables = Receivable::query()
                ->with(['client', 'bankBoletos'])
                ->whereKey($ids)
                ->get();

            if ($receivables->count() !== $ids->count()) {
                throw new RuntimeException('Uma ou mais parcelas selecionadas não estão mais disponíveis.');
            }

            $this->validateReceivables($receivables);

            foreach ($receivables as $receivable) {
                $this->validateExistingBoletos($receivable);
            }

            return null;
        } catch (RuntimeException $exception) {
            return $exception->getMessage();
        }
    }

    /**
     * @return array{pdf: string, filename: string, boletos: EloquentCollection, created: int, reused: int}
     */
    public function generate(Collection $selected): array
    {
        $ids = $selected->pluck('id')->filter()->unique()->values();

        if ($ids->isEmpty()) {
            throw new RuntimeException('Selecione ao menos uma parcela.');
        }

        $result = DB::transaction(function () use ($ids): array {
            /** @var EloquentCollection<int, Receivable> $receivables */
            $receivables = Receivable::query()
                ->with(['client', 'bankBoletos'])
                ->whereKey($ids)
                ->lockForUpdate()
                ->get()
                ->sortBy([
                    ['data_vencimento', 'asc'],
                    ['numero_parcela', 'asc'],
                    ['id', 'asc'],
                ])
                ->values();

            if ($receivables->count() !== $ids->count()) {
                throw new RuntimeException('Uma ou mais parcelas selecionadas não estão mais disponíveis.');
            }

            $this->validateReceivables($receivables);

            $boletos = new EloquentCollection;
            $created = 0;
            $reused = 0;

            foreach ($receivables as $receivable) {
                $this->validateExistingBoletos($receivable);

                $active = $receivable->bankBoletos
                    ->whereIn('status', ['pendente', 'emitido'])
                    ->sortByDesc('id')
                    ->values();

                if ($active->isNotEmpty()) {
                    $boletos->push($active->first());
                    $reused++;

                    continue;
                }

                $boletos->push(BankBoletoService::createFromReceivable($receivable));
                $created++;
            }

            return compact('receivables', 'boletos', 'created', 'reused');
        });

        $client = $result['receivables']->first()->client;

        return [
            'pdf' => BankBoletoService::renderBatchPdf($result['boletos']),
            'filename' => 'boletos-'.Str::slug($client->razao_social).'-'.now()->format('Y-m-d').'.pdf',
            'boletos' => $result['boletos'],
            'created' => $result['created'],
            'reused' => $result['reused'],
        ];
    }

    private function validateReceivables(EloquentCollection $receivables): void
    {
        if (! BankAccount::active()) {
            throw new RuntimeException('Nenhuma conta bancária ativa. Cadastre uma conta antes de gerar os boletos.');
        }

        if ($receivables->pluck('client_id')->unique()->count() !== 1) {
            throw new RuntimeException('Selecione parcelas de apenas um cliente.');
        }

        foreach ($receivables as $receivable) {
            $label = "Parcela {$receivable->numero_parcela}";

            if ($receivable->forma_pagamento !== 'boleto') {
                throw new RuntimeException("{$label} não utiliza boleto como forma de pagamento.");
            }

            if (! in_array($receivable->status, ['pendente', 'vencido'], true)) {
                throw new RuntimeException("{$label} está com status {$receivable->status} e não pode gerar boleto.");
            }

            if (! $receivable->client || blank($receivable->client->cnpj_cpf)) {
                throw new RuntimeException("{$label}: o cliente não possui CPF/CNPJ informado.");
            }

            if ((float) $receivable->valor <= 0 || ! $receivable->data_vencimento) {
                throw new RuntimeException("{$label} não possui valor e vencimento válidos.");
            }
        }
    }

    private function validateExistingBoletos(Receivable $receivable): void
    {
        $activeCount = $receivable->bankBoletos
            ->whereIn('status', ['pendente', 'emitido'])
            ->count();

        if ($activeCount > 1) {
            throw new RuntimeException("A parcela {$receivable->numero_parcela} possui mais de um boleto ativo.");
        }

        if ($activeCount === 0 && $receivable->bankBoletos->isNotEmpty()) {
            throw new RuntimeException("A parcela {$receivable->numero_parcela} possui apenas boleto cancelado, baixado ou pago.");
        }
    }
}
