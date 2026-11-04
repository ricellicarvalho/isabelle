<?php

namespace App\Filament\Pages;

use App\Models\Client;
use App\Services\CollaboratorsReportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\URL;
use UnitEnum;

class CollaboratorsReport extends Page
{
    protected string $view = 'filament.pages.collaborators-report';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Relatórios';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Colaboradores';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return auth()->user()?->can('ViewAny:Client') ?? false;
    }

    public function mount(): void
    {
        $this->form->fill([
            'client_id' => null,
            'search' => null,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Select::make('client_id')
                    ->label('Cliente')
                    ->options(fn (): array => Client::query()
                        ->orderBy('razao_social')
                        ->pluck('razao_social', 'id')
                        ->toArray())
                    ->placeholder('Todos os clientes')
                    ->searchable()
                    ->native(false)
                    ->live()
                    ->columnSpan(1),

                TextInput::make('search')
                    ->label('Pesquisar')
                    ->placeholder('Nome, telefone, local ou cliente')
                    ->live(debounce: 400)
                    ->columnSpan(1),
            ])
            ->columns(2);
    }

    protected function getViewData(): array
    {
        $report = CollaboratorsReportService::generate($this->data ?? []);
        $report['items'] = collect($report['items'])
            ->map(fn (array $item): array => $item + [
                'client_url' => \App\Filament\Resources\Clients\ClientResource::getUrl('edit', [
                    'record' => $item['client_id'],
                ]),
            ])
            ->all();

        return compact('report');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('imprimirPdf')
                ->label('Imprimir PDF')
                ->icon('heroicon-o-printer')
                ->color('danger')
                ->url(fn (): string => URL::temporarySignedRoute(
                    'reports.collaborators.pdf',
                    now()->addMinutes(30),
                    array_filter($this->data ?? [], fn ($value) => filled($value)),
                ))
                ->openUrlInNewTab(),
        ];
    }
}
