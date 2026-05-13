<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Event;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Database\Eloquent\Model;
use Saade\FilamentFullCalendar\Actions;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class CalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Event::class;

    public function config(): array
    {
        return [
            'initialView'    => 'dayGridMonth',
            'headerToolbar'  => [
                'left'   => 'prev,next today',
                'center' => 'title',
                'right'  => 'dayGridMonth,timeGridWeek,timeGridDay',
            ],
            'buttonText'     => [
                'today' => 'Hoje',
                'month' => 'Mês',
                'week'  => 'Semana',
                'day'   => 'Dia',
            ],
            'height'         => 'calc(100vh - 14rem)',
            'expandRows'     => true,
            'navLinks'       => true,
            'nowIndicator'   => true,
            'dayMaxEventRows' => 3,
        ];
    }

    public function fetchEvents(array $info): array
    {
        return Event::query()
            ->with(['client', 'user'])
            ->whereBetween('data_inicio', [$info['start'], $info['end']])
            ->get()
            ->map(fn (Event $event) => EventData::make()
                ->id($event->id)
                ->title(
                    ($event->client?->razao_social ? '[' . $event->client->razao_social . '] ' : '') .
                    $event->titulo
                )
                ->start($event->data_inicio)
                ->end($event->data_fim ?? $event->data_inicio)
                ->allDay($event->dia_inteiro)
                ->backgroundColor($event->cor ?? $this->tipoColor($event->tipo))
                ->borderColor($event->cor ?? $this->tipoColor($event->tipo))
                ->extendedProps([
                    'status'      => $event->status,
                    'tipo'        => $event->tipo,
                    'responsavel' => $event->user?->name,
                    'local'       => $event->local,
                ])
            )
            ->toArray();
    }

    /**
     * Pré-preenche o registro com a nova data ao arrastar e abre o modal de edição.
     */
    public function onEventDrop(array $event, array $oldEvent, array $relatedEvents, array $delta, ?array $oldResource, ?array $newResource): bool
    {
        $record = $this->resolveRecord($event['id']);
        $record->data_inicio = $event['start'] ?? $record->data_inicio;
        $record->data_fim = $event['end'] ?? $record->data_fim;

        if (array_key_exists('allDay', $event)) {
            $record->dia_inteiro = (bool) $event['allDay'];
        }

        $this->record = $record;

        $this->mountAction('edit');

        return false;
    }

    /**
     * Pré-preenche o registro com a nova duração ao redimensionar e abre o modal de edição.
     */
    public function onEventResize(array $event, array $oldEvent, array $relatedEvents, array $startDelta, array $endDelta): bool
    {
        $record = $this->resolveRecord($event['id']);
        $record->data_inicio = $event['start'] ?? $record->data_inicio;
        $record->data_fim = $event['end'] ?? $record->data_fim;

        $this->record = $record;

        $this->mountAction('edit');

        return false;
    }

    protected function headerActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Novo Evento')
                ->modalHeading('Criar Evento')
                ->modalSubmitActionLabel('Criar')
                ->modalCancelActionLabel('Cancelar')
                ->mutateFormDataUsing(function (array $data): array {
                    $data['created_by'] = auth()->id();

                    return $data;
                }),
        ];
    }

    protected function modalActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Editar')
                ->modalHeading('Editar Evento')
                ->modalSubmitActionLabel('Salvar')
                ->modalCancelActionLabel('Cancelar'),

            Actions\DeleteAction::make()
                ->label('Excluir')
                ->modalHeading('Excluir Evento')
                ->modalSubmitActionLabel('Excluir')
                ->modalCancelActionLabel('Cancelar'),
        ];
    }

    protected function viewAction(): \Filament\Actions\Action
    {
        return Actions\ViewAction::make()
            ->label('Visualizar')
            ->modalHeading('Visualizar Evento')
            ->modalCancelActionLabel('Fechar');
    }

    public function getFormSchema(): array
    {
        return [
            Grid::make(2)
                ->schema([
                    TextInput::make('titulo')
                        ->label('Título')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Select::make('tipo')
                        ->label('Tipo')
                        ->options([
                            'avaliacao_nr1' => 'Avaliação NR-1',
                            'devolutiva'    => 'Devolutiva',
                            'treinamento'   => 'Treinamento',
                            'palestra'      => 'Palestra',
                            'reuniao'       => 'Reunião',
                            'outro'         => 'Outro',
                        ])
                        ->default('outro')
                        ->required()
                        ->native(false)
                        ->live(),

                    Select::make('user_id')
                        ->label('Responsável')
                        ->options(User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))->pluck('name', 'id'))
                        ->required()
                        ->native(false)
                        ->default(fn () => auth()->id()),

                    Select::make('client_id')
                        ->label('Cliente')
                        ->options(Client::pluck('razao_social', 'id'))
                        ->searchable()
                        ->required()
                        ->live(),

                    Select::make('contract_id')
                        ->label('Contrato (opcional)')
                        ->options(function (Get $get): array {
                            $clientId = $get('client_id');
                            if (! $clientId) {
                                return [];
                            }

                            return Contract::where('client_id', $clientId)
                                ->pluck('numero', 'id')
                                ->toArray();
                        })
                        ->nullable()
                        ->native(false),

                    Toggle::make('dia_inteiro')
                        ->label('Dia Inteiro')
                        ->live()
                        ->columnSpanFull(),

                    DateTimePicker::make('data_inicio')
                        ->label('Início')
                        ->required()
                        ->seconds(false)
                        ->displayFormat('d/m/Y H:i'),

                    DateTimePicker::make('data_fim')
                        ->label('Fim')
                        ->seconds(false)
                        ->displayFormat('d/m/Y H:i')
                        ->after('data_inicio')
                        ->hidden(fn (Get $get): bool => (bool) $get('dia_inteiro')),

                    TextInput::make('local')
                        ->label('Local')
                        ->maxLength(255),

                    ColorPicker::make('cor')
                        ->label('Cor'),

                    Toggle::make('bloquear_agenda')
                        ->label('Bloquear agenda do responsável')
                        ->visible(fn (Get $get): bool => $get('tipo') === 'avaliacao_nr1')
                        ->columnSpanFull(),

                    Textarea::make('descricao')
                        ->label('Descrição')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ];
    }

    protected function resolveRecord(int | string $key): Model
    {
        return Event::findOrFail($key);
    }

    private function tipoColor(string $tipo): string
    {
        return match ($tipo) {
            'avaliacao_nr1' => '#7c3aed',
            'devolutiva'    => '#2563eb',
            'treinamento'   => '#16a34a',
            'palestra'      => '#d97706',
            'reuniao'       => '#6b7280',
            default         => '#6b7280',
        };
    }
}
