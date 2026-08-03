<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardFinanceService
{
    public function summary(Carbon $inicio, Carbon $fim): array
    {
        $totais = DreService::generate($inicio, $fim)['totais'];

        return [
            'receitas' => $totais['receitas'],
            'saidas' => $totais['custos'] + $totais['despesas'],
            'resultado' => $totais['lucro_liquido'],
            'margem_percentual' => $totais['margem_percentual'],
            'receber_vencidos' => $this->outstanding(Receivable::query(), beforeToday: true),
            'receber_hoje' => $this->outstanding(Receivable::query(), todayOnly: true),
            'pagar_vencidos' => $this->outstanding(Payable::query(), beforeToday: true),
            'pagar_hoje' => $this->outstanding(Payable::query(), todayOnly: true),
        ];
    }

    /**
     * @return array{total: float, count: int}
     */
    protected function outstanding(Builder $query, bool $beforeToday = false, bool $todayOnly = false): array
    {
        $query->whereIn('status', ['pendente', 'vencido']);

        if ($beforeToday) {
            $query->whereDate('data_vencimento', '<', today());
        } elseif ($todayOnly) {
            $query->whereDate('data_vencimento', today());
        }

        return [
            'total' => (float) (clone $query)->sum('valor'),
            'count' => (clone $query)->count(),
        ];
    }
}
