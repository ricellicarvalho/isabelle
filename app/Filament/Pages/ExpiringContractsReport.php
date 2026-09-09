<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Services\ExpiringContractsReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class ExpiringContractsReport extends Page
{
    protected string $view = 'filament.pages.expiring-contracts-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Vencimento de Contratos';

    public ?array $data = [];

    public array $contracts = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ExpiringContractsReport') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'prazo' => '30',
            'situacao' => 'a_vencer',
            'cliente' => '',
            'data_inicio' => null,
            'data_fim' => null,
        ]);

        $this->generateReport();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('prazo')
                    ->label('Vence em até')
                    ->options([
                        '7' => '7 dias',
                        '15' => '15 dias',
                        '30' => '30 dias',
                    ])
                    ->default('30')
                    ->disabled(fn (Get $get): bool => $get('situacao') === 'vencidos')
                    ->helperText('Aplicável aos contratos a vencer.')
                    ->native(false)
                    ->live(),

                Select::make('situacao')
                    ->label('Situação')
                    ->options([
                        'a_vencer' => 'Somente a vencer',
                        'vencidos' => 'Somente vencidos',
                        'todos' => 'Vencidos e a vencer',
                    ])
                    ->default('a_vencer')
                    ->native(false)
                    ->live(),

                Select::make('cliente')
                    ->label('Cliente')
                    ->options(fn () => Client::orderBy('razao_social')->pluck('razao_social', 'razao_social')->toArray())
                    ->placeholder('Todos os clientes')
                    ->searchable()
                    ->native(false)
                    ->live(),

                DatePicker::make('data_inicio')
                    ->label('Encerramento a partir de')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live(),

                DatePicker::make('data_fim')
                    ->label('Encerramento até')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->live(),
            ])
            ->columns(5);
    }

    public function updatedData(): void
    {
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $this->contracts = ExpiringContractsReportService::generate($this->data ?? [])
            ->map(fn (array $contract): array => $contract + [
                'url' => \App\Filament\Resources\Contracts\ContractResource::getUrl('edit', ['record' => $contract['id']]),
            ])
            ->all();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrirPdf')
                ->label('Abrir PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->url(fn (): string => URL::temporarySignedRoute(
                    'reports.expiring-contracts.pdf',
                    now()->addMinutes(30),
                    array_filter($this->data ?? [], fn ($v) => filled($v))
                ))
                ->openUrlInNewTab(),
        ];
    }
}
