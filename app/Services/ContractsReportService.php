<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Contract;
use App\Models\ContractVersion;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractsReportService
{
    public static function generate(array $filters): array
    {
        $clientId = $filters['client_id'] ?? null;
        $movementType = $filters['movement_type'] ?? 'all';
        $startDate = filled($filters['data_inicio'] ?? null)
            ? Carbon::parse($filters['data_inicio'])->toDateString()
            : null;
        $endDate = filled($filters['data_fim'] ?? null)
            ? Carbon::parse($filters['data_fim'])->toDateString()
            : null;

        $items = collect();

        if (in_array($movementType, ['all', 'new'], true)) {
            $newContracts = Contract::query()
                ->with('client')
                ->when($clientId, fn ($query, $id) => $query->where('client_id', $id))
                ->when($startDate, fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
                ->when($endDate, fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
                ->get();

            $items = $items->concat($newContracts->map(fn (Contract $contract): array => [
                'id' => $contract->id,
                'contract_id' => $contract->id,
                'movement_type' => 'new',
                'movement_date' => $contract->created_at?->format('d/m/Y'),
                'movement_timestamp' => $contract->created_at?->getTimestamp() ?? 0,
                'version_number' => 1,
                'numero' => $contract->numero,
                'cliente' => $contract->client?->razao_social ?? '—',
                'tipo_servico' => $contract->tipo_servico,
                'data_inicio' => $contract->data_inicio?->format('d/m/Y'),
                'data_fim' => $contract->data_fim?->format('d/m/Y'),
                'valor_total' => (float) $contract->valor_total,
                'status' => $contract->status,
            ]));
        }

        if (in_array($movementType, ['all', 'renewal'], true)) {
            $renewals = ContractVersion::query()
                ->with(['client', 'contract'])
                ->where('change_type', 'renewal')
                ->whereNotNull('activated_at')
                ->when($clientId, fn ($query, $id) => $query->where('client_id', $id))
                ->when($startDate, fn ($query, $date) => $query->whereDate('activated_at', '>=', $date))
                ->when($endDate, fn ($query, $date) => $query->whereDate('activated_at', '<=', $date))
                ->get();

            $items = $items->concat($renewals->map(fn (ContractVersion $version): array => [
                'id' => $version->id,
                'contract_id' => $version->contract_id,
                'movement_type' => 'renewal',
                'movement_date' => $version->activated_at?->format('d/m/Y'),
                'movement_timestamp' => $version->activated_at?->getTimestamp() ?? 0,
                'version_number' => $version->version_number,
                'numero' => $version->numero,
                'cliente' => $version->client?->razao_social ?? '—',
                'tipo_servico' => $version->tipo_servico,
                'data_inicio' => $version->data_inicio?->format('d/m/Y'),
                'data_fim' => $version->data_fim?->format('d/m/Y'),
                'valor_total' => (float) $version->valor_total,
                'status' => $version->contract?->status ?? $version->status,
            ]));
        }

        /** @var Collection<int, array<string, mixed>> $items */
        $items = $items
            ->sortByDesc(fn (array $item): array => [$item['movement_timestamp'], $item['id']])
            ->values();

        return [
            'items' => $items->map(fn (array $item): array => collect($item)->except('movement_timestamp')->all())->all(),
            'count' => $items->count(),
            'new_count' => $items->where('movement_type', 'new')->count(),
            'renewal_count' => $items->where('movement_type', 'renewal')->count(),
            'total_value' => (float) $items->sum('valor_total'),
            'filters' => [
                'cliente' => $clientId ? Client::find($clientId)?->razao_social : null,
                'client_id' => $clientId,
                'movement_type' => $movementType,
                'data_inicio' => $startDate,
                'data_fim' => $endDate,
            ],
        ];
    }
}
