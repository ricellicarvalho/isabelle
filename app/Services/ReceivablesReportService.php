<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Receivable;

class ReceivablesReportService
{
    public static function generate(array $filters): array
    {
        $clientId = $filters['client_id'] ?? null;
        $contractId = $filters['contract_id'] ?? null;
        $dataInicio = $filters['data_inicio'] ?? null;
        $dataFim = $filters['data_fim'] ?? null;
        $situacao = $filters['situacao'] ?? 'pago';
        $formaPagamento = $filters['forma_pagamento'] ?? null;
        $isPaid = $situacao === 'pago';
        $dateColumn = $isPaid ? 'data_pagamento' : 'data_vencimento';

        $query = Receivable::query()
            ->with(['client', 'contract'])
            ->when(
                $isPaid,
                fn ($query) => $query->where('status', 'pago')->whereNotNull('data_pagamento'),
                fn ($query) => $query->whereIn('status', ['pendente', 'vencido'])->whereNull('data_pagamento'),
            )
            ->when($clientId, fn ($query, $id) => $query->where('client_id', $id))
            ->when($contractId, fn ($query, $id) => $query->where('contract_id', $id))
            ->when($dataInicio, fn ($query, $date) => $query->whereDate($dateColumn, '>=', $date))
            ->when($dataFim, fn ($query, $date) => $query->whereDate($dateColumn, '<=', $date))
            ->when($isPaid && $formaPagamento, fn ($query) => $query->where('forma_pagamento', $formaPagamento))
            ->orderByDesc($dateColumn)
            ->orderByDesc('id');

        $receivables = $query->get();

        return [
            'items' => $receivables->map(fn (Receivable $receivable): array => [
                'id' => $receivable->id,
                'cliente' => $receivable->client?->razao_social ?? '—',
                'contrato' => $receivable->contract?->numero ?? '—',
                'descricao' => $receivable->descricao,
                'data' => ($isPaid ? $receivable->data_pagamento : $receivable->data_vencimento)?->format('d/m/Y'),
                'forma_pagamento' => $receivable->forma_pagamento,
                'valor' => (float) ($receivable->valor_pago ?? $receivable->valor),
            ])->toArray(),
            'total' => (float) $receivables->sum(
                fn (Receivable $receivable): float => (float) ($receivable->valor_pago ?? $receivable->valor)
            ),
            'count' => $receivables->count(),
            'filters' => [
                'cliente' => $clientId ? Client::find($clientId)?->razao_social : null,
                'contrato' => $contractId ? Contract::find($contractId)?->numero : null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'situacao' => $situacao,
                'forma_pagamento' => $formaPagamento,
            ],
        ];
    }
}
