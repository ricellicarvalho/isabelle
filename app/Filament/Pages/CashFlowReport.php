<?php

namespace App\Filament\Pages;

use App\Exports\CashFlowExport;
use App\Services\CashFlowService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnitEnum;

class CashFlowReport extends Page
{
    protected string $view = 'filament.pages.cash-flow-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Fluxo de Caixa';

    public ?array $data = [];

    public ?array $report = null;

    public function mount(): void
    {
        $this->form->fill([
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim' => now()->endOfMonth()->format('Y-m-d'),
            'regime' => 'caixa',
            'saldo_inicial' => 0,
        ]);
        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                DatePicker::make('data_inicio')->label('Data Início')->required()->native(false)->displayFormat('d/m/Y')->live(),
                DatePicker::make('data_fim')->label('Data Fim')->required()->native(false)->displayFormat('d/m/Y')->live(),
                Select::make('regime')
                    ->label('Regime')
                    ->options([
                        'caixa' => 'Caixa (data de pagamento)',
                        'competencia' => 'Competência (data de vencimento)',
                    ])
                    ->required()
                    ->native(false)
                    ->live(),
                TextInput::make('saldo_inicial')
                    ->label('Saldo Inicial')
                    ->numeric()
                    ->prefix('R$')
                    ->default(0)
                    ->live(onBlur: true),
            ])
            ->columns(4);
    }

    public function updatedData(): void
    {
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $inicio = Carbon::parse($this->data['data_inicio'] ?? now()->startOfMonth());
        $fim = Carbon::parse($this->data['data_fim'] ?? now()->endOfMonth());
        $regime = $this->data['regime'] ?? 'caixa';
        $saldoInicial = (float) ($this->data['saldo_inicial'] ?? 0);

        $this->report = CashFlowService::generate($inicio, $fim, $regime, $saldoInicial);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(fn (): BinaryFileResponse => $this->exportExcel()),
        ];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->generateReport();

        return Excel::download(new CashFlowExport($this->report), 'fluxo-caixa-' . now()->format('Y-m-d-His') . '.xlsx');
    }
}
