<?php

namespace App\Filament\Widgets;

use App\Services\DashboardFinanceService;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class FinanceStatsOverview extends BaseWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Visão Financeira';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('View:FinanceStatsOverview') ?? false;
    }

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        [$inicio, $fim] = $this->period();
        $summary = app(DashboardFinanceService::class)->summary($inicio, $fim);

        return [
            Stat::make('Receitas realizadas', $this->money($summary['receitas']))
                ->description('Recebidas pela data de pagamento')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),

            Stat::make('Custos + despesas realizados', $this->money($summary['saidas']))
                ->description('Pagos pela data de pagamento')
                ->descriptionIcon('heroicon-m-arrow-trending-down')
                ->color('warning'),

            Stat::make('Resultado líquido', $this->money($summary['resultado']))
                ->description($summary['resultado'] >= 0 ? 'Resultado positivo no período' : 'Resultado negativo no período')
                ->descriptionIcon($summary['resultado'] >= 0 ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle')
                ->color($summary['resultado'] >= 0 ? 'info' : 'danger'),

            Stat::make('Margem líquida', number_format($summary['margem_percentual'], 2, ',', '.').'%')
                ->description('Resultado líquido ÷ receitas')
                ->descriptionIcon('heroicon-m-chart-bar')
                ->color($summary['margem_percentual'] >= 0 ? 'success' : 'danger'),

            Stat::make('A receber — Vencidos', $this->money($summary['receber_vencidos']['total']))
                ->extraAttributes(['class' => 'dashboard-stat-receivable'])
                ->description("{$summary['receber_vencidos']['count']} conta(s) em atraso")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('A receber — Vencem hoje', $this->money($summary['receber_hoje']['total']))
                ->extraAttributes(['class' => 'dashboard-stat-receivable'])
                ->description("{$summary['receber_hoje']['count']} conta(s) com vencimento hoje")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),

            Stat::make('A pagar — Vencidos', $this->money($summary['pagar_vencidos']['total']))
                ->extraAttributes(['class' => 'dashboard-stat-payable'])
                ->description("{$summary['pagar_vencidos']['count']} conta(s) em atraso")
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('A pagar — Vencem hoje', $this->money($summary['pagar_hoje']['total']))
                ->extraAttributes(['class' => 'dashboard-stat-payable'])
                ->description("{$summary['pagar_hoje']['count']} conta(s) com vencimento hoje")
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color('warning'),
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    protected function period(): array
    {
        $inicio = Carbon::parse($this->pageFilters['data_inicio'] ?? now()->startOfMonth())->startOfDay();
        $fim = Carbon::parse($this->pageFilters['data_fim'] ?? now()->endOfMonth())->endOfDay();

        return [$inicio, $fim];
    }

    protected function money(float|int $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
