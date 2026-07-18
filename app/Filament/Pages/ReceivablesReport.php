<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Models\Contract;
use App\Services\ReceivablesReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class ReceivablesReport extends Page
{
    protected string $view = 'filament.pages.receivables-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Contas a Receber';

    public ?array $data = [];

    public array $report = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Receivable') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'client_id' => null,
            'contract_id' => null,
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim' => now()->endOfMonth()->format('Y-m-d'),
            'status' => null,
        ]);

        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('client_id')
                    ->label('Cliente')
                    ->options(fn (): array => Client::orderBy('razao_social')->pluck('razao_social', 'id')->toArray())
                    ->placeholder('Todos os clientes')
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->columnSpan(3),

                Select::make('contract_id')
                    ->label('Contrato')
                    ->options(fn (): array => Contract::query()
                        ->with('client')
                        ->orderBy('numero')
                        ->get()
                        ->mapWithKeys(fn (Contract $contract): array => [
                            $contract->id => $contract->numero . ' — ' . ($contract->client?->razao_social ?? 'Sem cliente'),
                        ])
                        ->toArray())
                    ->placeholder('Todos os contratos')
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->columnSpan(3),

                DatePicker::make('data_inicio')
                    ->label('Vencimento de')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live()
                    ->columnSpan(2),

                DatePicker::make('data_fim')
                    ->label('Vencimento até')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('data_inicio')
                    ->live()
                    ->columnSpan(2),

                Select::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
                    ])
                    ->placeholder('Todos os status')
                    ->native(false)
                    ->live()
                    ->columnSpan(2),
            ])
            ->columns(6);
    }

    public function updatedData(): void
    {
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $this->report = ReceivablesReportService::generate($this->data ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrirPdf')
                ->label('Abrir PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->url(fn (): string => URL::temporarySignedRoute(
                    'reports.receivables.pdf',
                    now()->addMinutes(30),
                    array_filter($this->data ?? [], fn ($value) => filled($value)),
                ))
                ->openUrlInNewTab(),
        ];
    }
}
