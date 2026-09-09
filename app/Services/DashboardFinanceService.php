<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class DashboardFinanceService
{
    public function summary(Carbon $inicio, Carbon $fim, ?int $accountId = null): array
    {
        $totais = DreService::generate($inicio, $fim, $accountId)['totais'];

        return [
            'unclassified' => FinancialCashService::unclassifiedCount($inicio, $fim, $accountId),
            'receitas' => $totais['receitas'],
            'saidas' => bcadd($totais['custos'], $totais['despesas'], 2),
            'resultado' => $totais['lucro_liquido'],
            'margem_percentual' => $totais['margem_percentual'],
            'receber_vencidos' => $this->outstanding(FinancialCashService::titleAccount(Receivable::query(), $accountId), beforeToday: true),
            'receber_hoje' => $this->outstanding(FinancialCashService::titleAccount(Receivable::query(), $accountId), todayOnly: true),
            'pagar_vencidos' => $this->outstanding(FinancialCashService::titleAccount(Payable::query(), $accountId), beforeToday: true),
            'pagar_hoje' => $this->outstanding(FinancialCashService::titleAccount(Payable::query(), $accountId), todayOnly: true),
        ];
    }

    /**
     * @return array{total: string, count: int}
     */
    protected function outstanding(Builder $query, bool $beforeToday = false, bool $todayOnly = false): array
    {
        $query->whereIn('status', ['pendente', 'vencido']);

        if ($beforeToday) {
            $query->whereDate('data_vencimento', '<', today());
        } elseif ($todayOnly) {
            $query->whereDate('data_vencimento', today());
        }

        $total = '0.00';
        $count = 0;
        foreach ($query->get() as $title) {
            $open = $title->saldo_aberto;
            if (bccomp($open, '0', 2) <= 0) {
                continue;
            }
            $total = bcadd($total, $open, 2);
            $count++;
        }

        return ['total' => $total, 'count' => $count];
    }
}
