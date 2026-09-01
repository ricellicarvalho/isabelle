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
        $formaPagamento = $filters['forma_pagamento'] ?? null;

        $query = Receivable::query()
            ->with(['client', 'contract'])
            ->whereNotNull('data_pagamento')
            ->when($clientId, fn ($query, $id) => $query->where('client_id', $id))
            ->when($contractId, fn ($query, $id) => $query->where('contract_id', $id))
            ->when($dataInicio, fn ($query, $date) => $query->whereDate('data_pagamento', '>=', $date))
            ->when($dataFim, fn ($query, $date) => $query->whereDate('data_pagamento', '<=', $date))
            ->when($formaPagamento, fn ($query, $value) => $query->where('forma_pagamento', $value))
            ->orderByDesc('data_pagamento')
            ->orderByDesc('id');

        $receivables = $query->get();

        return [
            'items' => $receivables->map(fn (Receivable $receivable): array => [
                'id' => $receivable->id,
                'cliente' => $receivable->client?->razao_social ?? '—',
                'contrato' => $receivable->contract?->numero ?? '—',
                'descricao' => $receivable->descricao,
                'pagamento' => $receivable->data_pagamento?->format('d/m/Y'),
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
                'forma_pagamento' => $formaPagamento,
            ],
        ];
    }
}
