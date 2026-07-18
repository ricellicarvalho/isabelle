<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Payable;
use App\Models\Supplier;

class PayablesReportService
{
    public static function generate(array $filters): array
    {
        $supplierId = $filters['supplier_id'] ?? null;
        $categoryId = $filters['category_id'] ?? null;
        $dataInicio = $filters['data_inicio'] ?? null;
        $dataFim = $filters['data_fim'] ?? null;
        $status = $filters['status'] ?? null;

        $query = Payable::query()
            ->with(['supplier', 'category'])
            ->when($supplierId, fn ($query, $id) => $query->where('supplier_id', $id))
            ->when($categoryId, fn ($query, $id) => $query->where('category_id', $id))
            ->when($dataInicio, fn ($query, $date) => $query->whereDate('data_vencimento', '>=', $date))
            ->when($dataFim, fn ($query, $date) => $query->whereDate('data_vencimento', '<=', $date))
            ->when($status, fn ($query, $value) => $query->where('status', $value))
            ->orderBy('data_vencimento')
            ->orderBy('id');

        $payables = $query->get();

        return [
            'items' => $payables->map(fn (Payable $payable): array => [
                'id' => $payable->id,
                'fornecedor' => $payable->supplier?->nome ?? $payable->fornecedor ?? '—',
                'categoria' => $payable->category?->descricao ?? '—',
                'descricao' => $payable->descricao,
                'vencimento' => $payable->data_vencimento?->format('d/m/Y'),
                'status' => $payable->status,
                'valor' => (float) $payable->valor,
            ])->toArray(),
            'total' => (float) $payables->sum('valor'),
            'count' => $payables->count(),
            'filters' => [
                'fornecedor' => $supplierId ? Supplier::find($supplierId)?->nome : null,
                'categoria' => $categoryId ? Category::find($categoryId)?->descricao : null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => $status,
            ],
        ];
    }
}
