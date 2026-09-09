<?php

namespace App\Services;

use App\Models\Contract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ExpiringContractsReportService
{
    /**
     * @param  array{prazo?: mixed, situacao?: mixed, cliente?: mixed, data_inicio?: mixed, data_fim?: mixed}  $filters
     * @return Collection<int, array<string, mixed>>
     */
    public static function generate(array $filters): Collection
    {
        $selectedDeadline = (string) ($filters['prazo'] ?? '30');
        $prazo = in_array($selectedDeadline, ['7', '15', '30'], true)
            ? (int) $selectedDeadline
            : 30;
        $selectedStatus = (string) ($filters['situacao'] ?? 'a_vencer');
        $situacao = in_array($selectedStatus, ['todos', 'vencidos', 'a_vencer'], true)
            ? $selectedStatus
            : 'a_vencer';
        $dataInicio = $filters['data_inicio'] ?? null;
        $dataFim = $filters['data_fim'] ?? null;
        $cliente = $filters['cliente'] ?? null;

        $query = Contract::query()
            ->whereIn('status', ['ativo', 'finalizado'])
            ->with('client')
            ->orderBy('data_fim');

        self::applyDateFilters($query, $situacao, $prazo, $dataInicio, $dataFim);

        if (filled($cliente)) {
            $query->whereHas('client', fn (Builder $query) => $query
                ->where('razao_social', 'like', "%{$cliente}%"));
        }

        return $query->get()->map(function (Contract $contract): array {
            $diasRestantes = (int) today()->diffInDays($contract->data_fim, false);

            return [
                'id' => $contract->id,
                'numero' => $contract->numero,
                'cliente' => $contract->client?->razao_social ?? '—',
                'tipo_servico' => $contract->tipo_servico,
                'valor_total' => (float) $contract->valor_total,
                'data_fim' => $contract->data_fim?->format('d/m/Y'),
                'dias_restantes' => $diasRestantes,
                'situacao_prazo' => self::deadlineLabel($diasRestantes),
            ];
        });
    }

    public static function deadlineLabel(int $daysRemaining): string
    {
        if ($daysRemaining < 0) {
            $daysOverdue = abs($daysRemaining);

            return 'Vencido há '.$daysOverdue.' '.($daysOverdue === 1 ? 'dia' : 'dias');
        }

        if ($daysRemaining === 0) {
            return 'Vence hoje';
        }

        return 'Vence em '.$daysRemaining.' '.($daysRemaining === 1 ? 'dia' : 'dias');
    }

    private static function applyDateFilters(
        Builder $query,
        string $situacao,
        int $prazo,
        mixed $dataInicio,
        mixed $dataFim,
    ): void {
        if (filled($dataInicio) || filled($dataFim)) {
            $query
                ->when(filled($dataInicio), fn (Builder $query) => $query->whereDate('data_fim', '>=', $dataInicio))
                ->when(filled($dataFim), fn (Builder $query) => $query->whereDate('data_fim', '<=', $dataFim));
        } elseif ($situacao !== 'vencidos') {
            $query->whereDate('data_fim', '<=', today()->addDays($prazo));
        }

        match ($situacao) {
            'vencidos' => $query->whereDate('data_fim', '<', today()),
            'a_vencer' => $query->whereDate('data_fim', '>=', today()),
            default => null,
        };
    }
}
