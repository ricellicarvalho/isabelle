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
        $status = $filters['status'] ?? null;

        $query = Receivable::query()
            ->with(['client', 'contract'])
            ->when($clientId, fn ($query, $id) => $query->where('client_id', $id))
            ->when($contractId, fn ($query, $id) => $query->where('contract_id', $id))
            ->when($dataInicio, fn ($query, $date) => $query->whereDate('data_vencimento', '>=', $date))
            ->when($dataFim, fn ($query, $date) => $query->whereDate('data_vencimento', '<=', $date))
            ->when($status, fn ($query, $value) => $query->where('status', $value))
            ->orderBy('data_vencimento')
            ->orderBy('id');

        $receivables = $query->get();

        return [
            'items' => $receivables->map(fn (Receivable $receivable): array => [
                'id' => $receivable->id,
                'cliente' => $receivable->client?->razao_social ?? '—',
                'contrato' => $receivable->contract?->numero ?? '—',
                'descricao' => $receivable->descricao,
                'parcela' => $receivable->numero_parcela,
                'vencimento' => $receivable->data_vencimento?->format('d/m/Y'),
                'status' => $receivable->status,
                'valor' => (float) $receivable->valor,
            ])->toArray(),
            'total' => (float) $receivables->sum('valor'),
            'count' => $receivables->count(),
            'filters' => [
                'cliente' => $clientId ? Client::find($clientId)?->razao_social : null,
                'contrato' => $contractId ? Contract::find($contractId)?->numero : null,
                'data_inicio' => $dataInicio,
                'data_fim' => $dataFim,
                'status' => $status,
            ],
        ];
    }
}
