<?php

namespace App\Filament\Widgets;

use App\Models\Payable;
use App\Models\Receivable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinanceStatsOverview extends BaseWidget
{
    protected ?string $heading = 'Visão Financeira do Mês';

    public static function canView(): bool
    {
        return auth()->user()?->can('View:FinanceStatsOverview') ?? false;
    }

    protected function getStats(): array
    {
        $inicioMes = now()->startOfMonth();
        $fimMes = now()->endOfMonth();

        $receitasRecebidas = (float) Receivable::where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
            ->sum('valor_pago');

        $receitasPendentes = (float) Receivable::whereIn('status', ['pendente', 'vencido'])
            ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
            ->sum('valor');

        $despesasPagas = (float) Payable::where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicioMes, $fimMes])
            ->sum('valor_pago');

        $despesasPendentes = (float) Payable::whereIn('status', ['pendente', 'vencido'])
            ->whereBetween('data_vencimento', [$inicioMes, $fimMes])
            ->sum('valor');

        $saldoProjetado = ($receitasRecebidas + $receitasPendentes) - ($despesasPagas + $despesasPendentes);

        $inadimplenciaQuery = Receivable::whereIn('status', ['pendente', 'vencido'])
            ->whereDate('data_vencimento', '<', now());
        $inadimplenciaValor = (float) $inadimplenciaQuery->sum('valor');
        $inadimplenciaCount = $inadimplenciaQuery->count();

        return [
            Stat::make('Receitas do Mês', 'R$ ' . number_format($receitasRecebidas + $receitasPendentes, 2, ',', '.'))
                ->description('Recebido R$ ' . number_format($receitasRecebidas, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Despesas do Mês', 'R$ ' . number_format($despesasPagas + $despesasPendentes, 2, ',', '.'))
                ->description('Pago R$ ' . number_format($despesasPagas, 2, ',', '.'))
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('Saldo Projetado', 'R$ ' . number_format($saldoProjetado, 2, ',', '.'))
                ->description($saldoProjetado >= 0 ? 'Resultado positivo' : 'Resultado negativo')
                ->descriptionIcon($saldoProjetado >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($saldoProjetado >= 0 ? 'info' : 'danger'),

            Stat::make('Inadimplência', 'R$ ' . number_format($inadimplenciaValor, 2, ',', '.'))
                ->description($inadimplenciaCount . ' parcela(s) vencida(s)')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
