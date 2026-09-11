<?php

namespace App\Filament\Resources\BankMovements\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankMovementForm
{
    private const MONEY_MASK = "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;";

    public static function configure(Schema $schema): Schema
    {
        return $schema->components([Section::make('Lançamento bancário')->columns(2)->components([
            Select::make('bank_account_id')->label('Conta')->relationship('bankAccount', 'nome', fn ($query) => $query->where('ativo', true))->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)->required()->searchable()->preload(),
            DateTimePicker::make('occurred_at')->label('Data e hora')->default(now())->required()->native(false)->displayFormat('d/m/Y H:i')->seconds(false),
            Select::make('direction')->label('Tipo')->options(['credit' => 'Crédito / Entrada', 'debit' => 'Débito / Saída'])->required()->native(false),
            self::moneyInput('amount'),
            TextInput::make('description')->label('Histórico')->required()->columnSpanFull(),
            TextInput::make('counterparty')->label('Favorecido / Cliente'),
            TextInput::make('reference')->label('Documento / Referência'),
            Select::make('category_id')->label('Categoria (Plano de Contas)')->relationship('category', 'descricao')->searchable()->preload()->required(),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
        ])->columnSpanFull()]);
    }

    public static function moneyInput(string $name, string $label = 'Valor'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->prefix('R$')
            ->placeholder('0,00')
            ->required()
            ->extraAlpineAttributes(['x-on:input' => self::MONEY_MASK])
            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state)))
            ->dehydrateStateUsing(fn ($state): string => self::parseMoney($state))
            ->rule(fn () => function (string $attribute, mixed $value, \Closure $fail) use ($label): void {
                if (bccomp(self::parseMoney($value), '0.00', 2) <= 0) {
                    $fail("O campo {$label} deve ser maior que zero.");
                }
            });
    }

    public static function parseMoney(mixed $state): string
    {
        if (is_numeric($state)) {
            return number_format((float) $state, 2, '.', '');
        }

        $digits = preg_replace('/\D/', '', (string) $state);

        return number_format(($digits === '' ? 0 : (int) $digits) / 100, 2, '.', '');
    }

    private static function formatMoney(mixed $state): string
    {
        return number_format((float) $state, 2, ',', '.');
    }
}
