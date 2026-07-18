<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\Supplier;
use App\Services\PayablesReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class PayablesReport extends Page
{
    protected string $view = 'filament.pages.payables-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Contas a Pagar';

    public ?array $data = [];

    public array $report = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Payable') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'supplier_id' => null,
            'category_id' => null,
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
                Select::make('supplier_id')
                    ->label('Fornecedor')
                    ->options(fn (): array => Supplier::orderBy('nome')->pluck('nome', 'id')->toArray())
                    ->placeholder('Todos os fornecedores')
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->columnSpan(3),

                Select::make('category_id')
                    ->label('Categoria')
                    ->options(fn (): array => Category::orderBy('descricao')->pluck('descricao', 'id')->toArray())
                    ->placeholder('Todas as categorias')
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
        $this->report = PayablesReportService::generate($this->data ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('abrirPdf')
                ->label('Abrir PDF')
                ->icon('heroicon-o-document-text')
                ->color('danger')
                ->url(fn (): string => URL::temporarySignedRoute(
                    'reports.payables.pdf',
                    now()->addMinutes(30),
                    array_filter($this->data ?? [], fn ($value) => filled($value)),
                ))
                ->openUrlInNewTab(),
        ];
    }
}
