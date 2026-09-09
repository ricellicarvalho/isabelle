<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Filament\Resources\Contracts\Schemas\ContractForm;
use App\Models\BankAccount;
use App\Services\BankMovementService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class ReceivablesRelationManager extends RelationManager
{
    protected static string $relationship = 'receivables';

    protected static ?string $title = 'Parcelas';

    protected static ?string $modelLabel = 'parcela';

    protected static ?string $pluralModelLabel = 'parcelas';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('numero_parcela')
                    ->label('Nº Parcela')
                    ->numeric()
                    ->disabled(),

                TextInput::make('valor')->disabled(fn ($record) => $record?->settlements()->exists() ?? false)
                    ->label('Valor')
                    ->required()
                    ->prefix('R$')
                    ->placeholder('0,00')
                    ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                    ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state))),

                TextInput::make('valor_pago')->disabled(fn ($record) => $record?->settlements()->exists() ?? false)
                    ->label('Valor Pago')
                    ->prefix('R$')
                    ->placeholder('0,00')
                    ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                    ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                    ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state))),

                DatePicker::make('data_vencimento')
                    ->label('Vencimento')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                DatePicker::make('data_pagamento')->disabled(fn ($record) => $record?->settlements()->exists() ?? false)
                    ->label('Pagamento')
                    ->native(false)
                    ->displayFormat('d/m/Y'),

                Select::make('status')->disableOptionWhen(fn (string $value, $record) => $value === 'pago' && $record?->status !== 'pago')->helperText('Para novos pagamentos, salve o título e use Dar baixa ou Receber.')->disabled(fn ($record) => $record?->settlements()->exists() ?? false)
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
                    ])
                    ->required()
                    ->native(false),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descricao')
            ->defaultSort('numero_parcela', 'asc')
            ->columns([
                TextColumn::make('numero_parcela')
                    ->label('Nº')
                    ->sortable(),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(40),

                TextColumn::make('saldo_aberto')->label('Saldo aberto')->money('BRL'),
                TextColumn::make('situacao_financeira')->label('Baixa')->badge(),
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
                    ->placeholder('—'),

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
            ->headerActions([])
            ->actions([
                \App\Filament\Actions\FinancialSettlementActions::settle(),
                \App\Filament\Actions\FinancialSettlementActions::reverse(),
                \App\Filament\Actions\FinancialSettlementActions::history(),
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // RN05 - Quitação em Lote
                    BulkAction::make('marcarPago')->visible(fn () => auth()->user()->can('Settle:Receivable'))
                        ->label('Baixar saldo em lote')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Select::make('bank_account_id')->disabled(fn ($record) => $record?->settlements()->exists() ?? false)->label('Conta bancária')->options(fn () => BankAccount::query()->where('ativo', true)->get()->pluck('display_name', 'id'))->required()->searchable(),
                            DatePicker::make('data_pagamento')->disabled(fn ($record) => $record?->settlements()->exists() ?? false)->label('Data do recebimento')->default(today())->minDate(BankMovementService::CONTROL_START)->required(),
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
                                ->title("{$count} parcela(s) marcada(s) como pagas")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }

    private static function parseMoney(mixed $state): ?float
    {
        // Reaproveita a implementação canônica do ContractForm, que trata os
        // valores intermediários gerados pela máscara Alpine (ex.: "0,15000")
        // capturados pelo wire:model antes do reformat. Mantê-las separadas já
        // causou divergência: aqui o valor saía deslocado (150,00 -> 15,00).
        return ContractForm::parseMoney($state);
    }

    private static function formatMoney(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        return number_format((float) $state, 2, ',', '.');
    }
}
