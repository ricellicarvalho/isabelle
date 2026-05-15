<?php

namespace App\Filament\Resources\Events\Tables;

use App\Models\Event;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EventsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_inicio', 'asc')
            ->columns([
                TextColumn::make('data_inicio')
                    ->label('Início')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'avaliacao_nr1'   => 'Avaliação NR-1',
                        'devolutiva'      => 'Devolutiva',
                        'treinamento'     => 'Treinamento',
                        'palestra'        => 'Palestra',
                        'reuniao'         => 'Reunião',
                        'formacao_humana' => 'Formação Humana',
                        default           => 'Outro',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'avaliacao_nr1'   => 'purple',
                        'devolutiva'      => 'info',
                        'treinamento'     => 'success',
                        'palestra'        => 'warning',
                        'reuniao'         => 'gray',
                        'formacao_humana' => 'primary',
                        default           => 'gray',
                    }),

                TextColumn::make('client.razao_social')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30),

                TextColumn::make('responsaveis')
                    ->label('Responsável(is)')
                    ->state(function (Event $record): string {
                        $usersMap = User::pluck('name', 'id');
                        $ids = $record->user_ids ?? ($record->user_id ? [$record->user_id] : []);

                        return collect($ids)
                            ->map(fn ($id) => $usersMap[$id] ?? null)
                            ->filter()
                            ->implode(', ') ?: '—';
                    })
                    ->searchable(query: function ($query, string $search): void {
                        $query->whereHas('user', fn ($q) => $q->where('name', 'like', "%{$search}%"));
                    })
                    ->toggleable(),

                TextColumn::make('local')
                    ->label('Local')
                    ->limit(25)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'agendado'  => 'warning',
                        'realizado' => 'success',
                        'cancelado' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'agendado'  => 'Agendado',
                        'realizado' => 'Realizado',
                        'cancelado' => 'Cancelado',
                    }),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'avaliacao_nr1'   => 'Avaliação NR-1',
                        'devolutiva'      => 'Devolutiva',
                        'treinamento'     => 'Treinamento',
                        'palestra'        => 'Palestra',
                        'reuniao'         => 'Reunião',
                        'formacao_humana' => 'Formação Humana',
                        'outro'           => 'Outro',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'agendado'  => 'Agendado',
                        'realizado' => 'Realizado',
                        'cancelado' => 'Cancelado',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ]);
    }
}
