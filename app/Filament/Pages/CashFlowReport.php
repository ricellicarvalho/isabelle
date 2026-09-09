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

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:CashFlowReport') ?? false;
    }

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
                DatePicker::make('data_fim')->label('Data Fim')->afterOrEqual('data_inicio')->required()->native(false)->displayFormat('d/m/Y')->live(),
                Select::make('bank_account_id')->label('Conta financeira')->options(fn () => \App\Models\BankAccount::orderBy('nome')->get()->pluck('display_name', 'id'))->placeholder('Todas as contas')->searchable()->live(),
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
                    ->label('Saldo inicial legado / competência')->helperText('No regime de caixa desde 01/08/2026, o saldo é calculado pelas contas.')
                    ->disabled(fn (\Filament\Schemas\Components\Utilities\Get $get) => $get('regime') === 'caixa' && ($get('data_inicio') ?? '') >= \App\Services\BankMovementService::CONTROL_START)
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
        \Illuminate\Support\Facades\Gate::authorize('View:CashFlowReport');
        $inicio = Carbon::parse($this->data['data_inicio'] ?? now()->startOfMonth());
        $fim = Carbon::parse($this->data['data_fim'] ?? now()->endOfMonth());
        $regime = $this->data['regime'] ?? 'caixa';
        $saldoInicial = (string) ($this->data['saldo_inicial'] ?? '0.00');

        $this->report = CashFlowService::generate($inicio, $fim, $regime, $saldoInicial, filled($this->data['bank_account_id'] ?? null) ? (int) $this->data['bank_account_id'] : null);
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

        return Excel::download(new CashFlowExport($this->report), 'fluxo-caixa-'.now()->format('Y-m-d-His').'.xlsx');
    }
}
