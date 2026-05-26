<?php

namespace App\Filament\Pages;

use App\Models\Contract;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use App\Models\Client;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class ExpiringContractsReport extends Page
{
    protected string $view = 'filament.pages.expiring-contracts-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Contratos a Vencer';

    public ?array $data = [];

    public array $contracts = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('View:ExpiringContractsReport') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'prazo'       => '30',
            'cliente'     => '',
            'data_inicio' => null,
            'data_fim'    => null,
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
                        '7'  => '7 dias',
                        '15' => '15 dias',
                        '30' => '30 dias',
                    ])
                    ->default('30')
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
            ->columns(4);
    }

    public function updatedData(): void
    {
        $this->generateReport();
    }

    public function generateReport(): void
    {
        $prazo      = $this->data['prazo'] ?? '30';
        $cliente    = $this->data['cliente'] ?? '';
        $dataInicio = $this->data['data_inicio'] ?? null;
        $dataFim    = $this->data['data_fim'] ?? null;

        $query = Contract::query()
            ->where('status', 'ativo')
            ->with('client')
            ->orderBy('data_fim');

        if ($dataInicio && $dataFim) {
            $query->whereDate('data_fim', '>=', $dataInicio)
                  ->whereDate('data_fim', '<=', $dataFim);
        } else {
            $query->whereDate('data_fim', '>=', today())
                  ->whereDate('data_fim', '<=', today()->addDays((int) $prazo));
        }

        if (filled($cliente)) {
            $query->whereHas('client', fn ($q) => $q->where('razao_social', 'like', "%{$cliente}%"));
        }

        $this->contracts = $query->get()->map(function (Contract $c): array {
            $diasRestantes = (int) today()->diffInDays($c->data_fim, false);

            return [
                'id'             => $c->id,
                'numero'         => $c->numero,
                'cliente'        => $c->client?->razao_social ?? '—',
                'tipo_servico'   => $c->tipo_servico,
                'valor_total'    => (float) $c->valor_total,
                'data_fim'       => $c->data_fim?->format('d/m/Y'),
                'dias_restantes' => $diasRestantes,
                'url'            => \App\Filament\Resources\Contracts\ContractResource::getUrl('edit', ['record' => $c->getKey()]),
            ];
        })->toArray();
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
