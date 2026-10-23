<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Services\ContractsReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class ContractsReport extends Page
{
    protected string $view = 'filament.pages.contracts-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Movimentação de Contratos';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Contract') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'client_id' => null,
            'movement_type' => 'all',
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim' => now()->endOfMonth()->format('Y-m-d'),
        ]);
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
                    ->columnSpan(2),

                Select::make('movement_type')
                    ->label('Tipo de movimentação')
                    ->options([
                        'all' => 'Novos e renovados',
                        'new' => 'Novos contratos',
                        'renewal' => 'Contratos renovados',
                    ])
                    ->required()
                    ->native(false)
                    ->live()
                    ->columnSpan(2),

                DatePicker::make('data_inicio')
                    ->label('Período de')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live()
                    ->columnSpan(1),

                DatePicker::make('data_fim')
                    ->label('Período até')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('data_inicio')
                    ->live()
                    ->columnSpan(1),
            ])
            ->columns(6);
    }

    protected function getViewData(): array
    {
        $report = ContractsReportService::generate($this->data ?? []);
        $report['items'] = collect($report['items'])
            ->map(fn (array $item): array => $item + [
                'url' => \App\Filament\Resources\Contracts\ContractResource::getUrl('edit', ['record' => $item['contract_id']]),
            ])
            ->all();

        return compact('report');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrirPdf')
                ->label('Abrir PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->url(fn (): string => URL::temporarySignedRoute(
                    'reports.contracts.pdf',
                    now()->addMinutes(30),
                    array_filter($this->data ?? [], fn ($value) => filled($value)),
                ))
                ->openUrlInNewTab(),
        ];
    }
}
