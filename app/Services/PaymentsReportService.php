<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Payable;
use App\Models\Supplier;

class PaymentsReportService
{
    public static function generate(array $filters): array
    {
        $supplierId = $filters['supplier_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $dataInicio = $filters['data_inicio'] ?? null;
        $dataFim = $filters['data_fim'] ?? null;
        $formaPagamento = $filters['forma_pagamento'] ?? null;

        $query = Payable::query()
            ->with(['supplier', 'category'])
            ->whereNotNull('data_pagamento')
            ->when($supplierId, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($categoryId, fn ($query, $id) => $query->where('category_id', $id))
            ->when($dataInicio, fn ($query, $date) => $query->whereDate('data_pagamento', '>=', $date))
            ->when($dataFim, fn ($query, $date) => $query->whereDate('data_pagamento', '<=', $date))
            ->when($formaPagamento, fn ($query, $value) => $query->where('forma_pagamento', $value))
            ->orderByDesc('data_pagamento')
            ->orderByDesc('id');

        $payments = $query->get();

        return [
            'items' => $payments->map(fn (Payable $payable): array => [
                'id' => $payable->id,
                'fornecedor' => $payable->supplier?->nome ?? $payable->fornecedor ?? '—',
                'categoria' => $payable->category?->descricao ?? '—',
                'descricao' => $payable->descricao,
                'pagamento' => $payable->data_pagamento?->format('d/m/Y'),
                'forma_pagamento' => $payable->forma_pagamento,
                'valor' => (float) ($payable->valor_pago ?? $payable->valor),
            ])->toArray(),
            'total' => (float) $payments->sum(fn (Payable $payable): float => (float) ($payable->valor_pago ?? $payable->valor)),
            'count' => $payments->count(),
            'filters' => [
                'fornecedor' => $supplierId ? Supplier::find($supplierId)?->nome : null,
                'categoria' => $categoryId ? Category::find($categoryId)?->descricao : null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'forma_pagamento' => $formaPagamento,
            ],
        ];
    }
}
