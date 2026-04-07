<?php

namespace App\Filament\Resources\Payables\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class PayablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_vencimento', 'asc')
            ->columns([
                TextColumn::make('fornecedor')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->limit(30),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(35),

                TextColumn::make('category.descricao')
                    ->label('Categoria')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('dias_atraso')
                    ->label('Atraso')
                    ->state(function ($record): ?string {
                        if ($record->status === 'pago' || $record->status === 'cancelado') {
                            return null;
                        }
                        $dias = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($record->data_vencimento)->startOfDay(), false);
                        if ($dias >= 0) {
                            return null;
                        }

                        return abs($dias) . ' dias';
                    })
                    ->badge()
                    ->color('danger')
                    ->placeholder('—'),

                TextColumn::make('data_pagamento')
                    ->label('Pagamento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'pago' => 'success',
                        'cancelado' => 'gray',
                        'vencido' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
                    ]),

                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'descricao')
                    ->searchable()
                    ->preload(),

                Filter::make('vencidas')
                    ->label('Vencidas (não pagas)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['pendente', 'vencido'])
                        ->whereDate('data_vencimento', '<', now()))
                    ->toggle(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // RN05 - Quitação em Lote
                    BulkAction::make('marcarPago')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pendente' || $record->status === 'vencido') {
                                    $record->update([
                                        'status' => 'pago',
                                        'data_pagamento' => now(),
                                        'valor_pago' => $record->valor,
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} conta(s) marcada(s) como paga(s)")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
