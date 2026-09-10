<?php

namespace App\Filament\Resources\Payables\Tables;

use App\Filament\Resources\Payables\Pages\ListPayables;
use App\Models\BankAccount;
use App\Services\BankMovementService;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('saldo_aberto')->label('Saldo aberto')->money('BRL'),
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

                TextColumn::make('bankAccount.nome')->label('Conta')->placeholder('Não informada')->searchable()->toggleable(),

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
                SelectFilter::make('bank_account_id')->label('Conta prevista / baixas')->options(fn () => BankAccount::orderBy('nome')->get()->pluck('display_name', 'id'))->searchable()
                    ->query(fn (Builder $query, array $data) => \App\Services\FinancialCashService::titleAccount($query, filled($data['value'] ?? null) ? (int) $data['value'] : null)),
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
                Filter::make('sem_baixa_historica')
                    ->label('Pagos sem baixa bancária')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pago')
                        ->whereDate('data_pagamento', '>=', BankMovementService::CONTROL_START)
                        ->whereDoesntHave('settlements'))
                    ->toggle(),
            ])
            ->actions([
                ActionGroup::make([
                    \App\Filament\Actions\FinancialSettlementActions::settle(),
                    \App\Filament\Actions\FinancialSettlementActions::reverse(),
                    \App\Filament\Actions\FinancialSettlementActions::history(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\FinancialSettlementActions::historicalBulk('Payable'),
                    // RN05 - Quitação em Lote
                    BulkAction::make('marcarPago')->visible(fn () => auth()->user()->can('Settle:Payable'))
                        ->label('Baixar saldo em lote')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Select::make('bank_account_id')->label('Conta bancária')->options(fn () => BankAccount::query()->where('ativo', true)->get()->pluck('display_name', 'id'))->required()->searchable(),
                            DatePicker::make('data_pagamento')->label('Data do pagamento')->default(today())->minDate(BankMovementService::CONTROL_START)->required(),
                        ])
                        ->requiresConfirmation()
                        ->action(function (Collection $records, array $data): void {
                            $count = \Illuminate\Support\Facades\DB::transaction(function () use ($records, $data) {
                                $count = 0;
                                foreach ($records->sortBy('id') as $record) {
                                    if ($record->status === 'pendente' || $record->status === 'vencido') {
                                        \Illuminate\Support\Facades\Gate::authorize('Settle:'.class_basename($record));
                                        app(BankMovementService::class)->settle($record, BankAccount::findOrFail($data['bank_account_id']), $data['data_pagamento'], $record->saldo_aberto);
                                        $count++;
                                    }
                                }

                                return $count;
                            });

                            Notification::make()
                                ->title("{$count} conta(s) marcada(s) como paga(s)")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ])->dropdownWidth(Width::Large),
            ])
            ->contentFooter(function (ListPayables $livewire) {
                $resumo = $livewire->getSelectedPayablesSummary();

                return view('filament.resources.payables.selected-total-footer', $resumo);
            });
    }
}
