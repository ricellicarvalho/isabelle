<?php

namespace App\Filament\Resources\Events\Schemas;

use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class EventForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->icon(Heroicon::CalendarDays)
                    ->columns(2)
                    ->components([
                        TextInput::make('titulo')
                            ->label('Título')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('tipo')
                            ->label('Tipo')
                            ->options([
                                'avaliacao_nr1'   => 'Avaliação NR-1',
                                'devolutiva'      => 'Devolutiva',
                                'treinamento'     => 'Treinamento',
                                'palestra'        => 'Palestra',
                                'reuniao'         => 'Reunião',
                                'formacao_humana' => 'Formação Humana',
                                'outro'           => 'Outro',
                            ])
                            ->default('outro')
                            ->required()
                            ->native(false)
                            ->live(),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'agendado'  => 'Agendado',
                                'realizado' => 'Realizado',
                                'cancelado' => 'Cancelado',
                            ])
                            ->default('agendado')
                            ->required()
                            ->native(false),

                        Select::make('client_id')
                            ->label('Cliente')
                            ->relationship('client', 'razao_social')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->columnSpanFull(),

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
                            ->searchable()
                            ->native(false)
                            ->nullable()
                            ->columnSpanFull(),

                        Select::make('user_ids')
                            ->label('Responsáveis')
                            ->options(User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'super_admin'))->pluck('name', 'id'))
                            ->searchable()
                            ->required()
                            ->multiple()
                            ->native(false)
                            ->default(fn () => auth()->user()?->hasRole('super_admin') ? [] : [auth()->id()]),
                    ]),

                Section::make('Data e Horário')
                    ->icon(Heroicon::Clock)
                    ->columns(2)
                    ->components([
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
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),

                Section::make('Configurações')
                    ->icon(Heroicon::Cog6Tooth)
                    ->columns(2)
                    ->components([
                        Toggle::make('bloquear_agenda')
                            ->label('Bloquear agenda do responsável')
                            ->helperText('Impede que o responsável seja alocado em outro evento no mesmo horário.')
                            ->visible(fn (Get $get): bool => $get('tipo') === 'avaliacao_nr1')
                            ->columnSpanFull(),

                        ColorPicker::make('cor')
                            ->label('Cor no Calendário')
                            ->default(null),

                        Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
