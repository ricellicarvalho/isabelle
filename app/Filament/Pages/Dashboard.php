<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ExpenseCompositionChart;
use App\Filament\Widgets\FinanceStatsOverview;
use App\Filament\Widgets\OverdueReceivablesTable;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Pages\Dashboard\Concerns\HasFiltersForm;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class Dashboard extends BaseDashboard
{
    use HasFiltersForm;

    protected static ?string $title = 'Dashboard Financeiro';

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Período de competência por pagamento')
                    ->description('Receitas e despesas realizadas são consideradas pela data de pagamento.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('data_inicio')
                            ->label('Data inicial')
                            ->default(now()->startOfMonth())
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('data_fim')
                            ->label('Data final')
                            ->default(now()->endOfMonth())
                            ->afterOrEqual('data_inicio')
                            ->required()
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ]),
            ]);
    }

    public function getWidgets(): array
    {
        return [
            FinanceStatsOverview::class,
            ExpenseCompositionChart::class,
            OverdueReceivablesTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }
}
