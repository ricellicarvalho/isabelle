<?php

namespace App\Filament\Actions;

use App\Models\BankAccount;
use App\Models\Receivable;
use App\Services\BankMovementService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class FinancialSettlementActions
{
    public static function settle(): Action
    {
        return Action::make('darBaixa')
            ->label(fn ($record) => self::isLegacyPaidWithoutSettlement($record)
                ? 'Registrar baixa histórica'
                : ($record instanceof Receivable ? 'Receber' : 'Dar baixa'))
            ->icon('heroicon-o-check-circle')
            ->visible(fn ($record) => auth()->user()->can('Settle:'.class_basename($record))
                && $record->status !== 'cancelado'
                && ($record->status !== 'pago' || self::isLegacyPaidWithoutSettlement($record)))
            ->schema([
                Hidden::make('idempotency_key')->default(fn () => (string) Str::uuid()),
                Select::make('bank_account_id')->label('Conta financeira')->options(fn () => BankAccount::where('ativo', true)->get()->pluck('display_name', 'id'))->required()->searchable(),
                DatePicker::make('date')->label('Data efetiva')->default(fn ($record) => self::isLegacyPaidWithoutSettlement($record) ? $record->data_pagamento : today())->minDate(BankMovementService::CONTROL_START)->required(),
                TextInput::make('amount')->label('Principal a baixar')->default(fn ($record) => self::isLegacyPaidWithoutSettlement($record) ? ($record->valor_pago ?: $record->valor) : $record->saldo_aberto)->numeric()->minValue('0.01')->step('0.01')->required(),
                TextInput::make('interest')->label('Juros')->numeric()->minValue(0)->step('0.01')->default('0.00')->required(),
                TextInput::make('penalty')->label('Multa')->numeric()->minValue(0)->step('0.01')->default('0.00')->required(),
                TextInput::make('discount')->label('Desconto')->numeric()->minValue(0)->step('0.01')->default('0.00')->required(),
                TextInput::make('fee')->label('Tarifa bancária')->helperText('Acrescida ao pagamento; deduzida do recebimento.')->numeric()->minValue(0)->step('0.01')->default('0.00')->required(),
                Select::make('method')->label('Forma de pagamento')->options(['pix' => 'PIX', 'boleto' => 'Boleto', 'transferencia' => 'Transferência', 'dinheiro' => 'Dinheiro', 'cartao' => 'Cartão'])->required(),
                TextInput::make('reference')->label('Documento/Referência')->maxLength(255),
                Textarea::make('notes')->label('Observação'),
            ])
            ->action(function ($record, array $data, $livewire) {
                Gate::authorize('Settle:'.class_basename($record));
                if (self::isLegacyPaidWithoutSettlement($record)) {
                    $data['origin'] = 'legacy_migration';
                    $data['idempotency_key'] = 'legacy:'.$record->getMorphClass().':'.$record->id;
                }
                app(BankMovementService::class)->settle($record, BankAccount::findOrFail($data['bank_account_id']), $data['date'], $data['amount'], $data['method'], $data);
                self::refreshRecord($record, $livewire);
            });
    }

    private static function isLegacyPaidWithoutSettlement($record): bool
    {
        return $record->status === 'pago'
            && $record->data_pagamento?->gte(BankMovementService::CONTROL_START)
            && ! $record->settlements()->exists();
    }

    private static function refreshRecord($record, $livewire): void
    {
        $record->refresh();
        if (method_exists($livewire, 'refreshFormData')) {
            $livewire->refreshFormData(['valor', 'valor_pago', 'data_pagamento', 'forma_pagamento', 'bank_account_id', 'status']);
        }
    }

    public static function history(): Action
    {
        return Action::make('historicoBaixas')->label('Histórico de baixas')
            ->visible(fn ($record) => $record->settlements()->exists())
            ->modalContent(fn ($record) => view('filament.financial-settlements.history', ['settlements' => $record->settlements()->with(['bankAccount', 'creator', 'reversedBy'])->orderByDesc('settled_at')->get()]))
            ->modalSubmitAction(false)->modalCancelActionLabel('Fechar');
    }

    public static function reverse(): Action
    {
        return Action::make('estornarBaixa')->label('Estornar baixa')->color('danger')
            ->visible(fn ($record) => auth()->user()->can('Reverse:'.class_basename($record)) && $record->settlements()->where('status', 'confirmed')->exists())
            ->schema([
                Select::make('settlement_id')->label('Baixa')->options(fn ($record) => $record->settlements()->with('bankAccount')->where('status', 'confirmed')->get()->mapWithKeys(fn ($s) => [$s->id => '#'.$s->id.' · '.$s->settled_at->format('d/m/Y').' · '.$s->bankAccount->display_name.' · R$ '.$s->amount]))->required(),
                Textarea::make('reason')->label('Justificativa')->required()->maxLength(2000),
            ])
            ->requiresConfirmation()
            ->action(function ($record, array $data, $livewire) {
                Gate::authorize('Reverse:'.class_basename($record));
                app(BankMovementService::class)->reverse($record->settlements()->findOrFail($data['settlement_id']), $data['reason']);
                self::refreshRecord($record, $livewire);
            });
    }
}
