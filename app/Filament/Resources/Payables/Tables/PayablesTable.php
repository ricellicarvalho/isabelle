<?php

namespace App\Filament\Resources\Payables\Tables;

use App\Filament\Resources\Payables\Pages\ListPayables;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
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
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw(
                    "CASE
                        WHEN status IN ('pendente', 'vencido') AND data_vencimento < ? THEN 0
                        WHEN status IN ('pendente', 'vencido') THEN 1
                        ELSE 2
                    END",
                    [today()->toDateString()],
                )
                ->orderByRaw(
                    "CASE
                        WHEN status IN ('pendente', 'vencido') AND data_vencimento < ? THEN data_vencimento
                    END DESC",
                    [today()->toDateString()],
                )
                ->orderBy('data_vencimento'))
            ->columns([
                TextColumn::make('supplier.nome')
                    ->label('Fornecedor')
                    ->searchable()
                    ->sortable()
                    ->placeholder('—')
                    ->limit(30),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(35),

                TextColumn::make('recurrence_sequence')
                    ->label('Recorrência')
                    ->formatStateUsing(fn ($state, $record): string => $state
                        ? "{$state}/{$record->recurrence_total}"
                        : '—')
                    ->badge()
                    ->color('info')
                    ->placeholder('—')
                    ->toggleable(),

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

                        return abs($dias).' dias';
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

                SelectFilter::make('supplier_id')
                    ->label('Fornecedor')
                    ->relationship('supplier', 'nome')
                    ->searchable()
                    ->preload(),

                Filter::make('data_vencimento')
                    ->label('Vencimento')
                    ->schema([
                        DatePicker::make('de')
                            ->label('Vencimento de')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('ate')
                            ->label('Vencimento até')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['de'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '>=', $date),
                        )
                        ->when(
                            $data['ate'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '<=', $date),
                        )),

                Filter::make('vencidas')
                    ->label('Vencidas (não pagas)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['pendente', 'vencido'])
                        ->whereDate('data_vencimento', '<', now()))
                    ->toggle(),

                Filter::make('recorrentes')
                    ->label('Contas recorrentes')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('payable_recurrence_id'))
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
            ])
            ->contentFooter(function (ListPayables $livewire) {
                $resumo = $livewire->getSelectedPayablesSummary();

                return view('filament.resources.payables.selected-total-footer', $resumo);
            });
    }
}
