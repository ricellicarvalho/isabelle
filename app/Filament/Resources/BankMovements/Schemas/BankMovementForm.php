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
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([Section::make('Lançamento bancário')->columns(2)->components([
            Select::make('bank_account_id')->label('Conta')->relationship('bankAccount', 'nome', fn ($query) => $query->where('ativo', true))->getOptionLabelFromRecordUsing(fn ($record): string => $record->display_name)->required()->searchable()->preload(),
            DateTimePicker::make('occurred_at')->label('Data e hora')->default(now())->required()->native(false),
            Select::make('direction')->label('Tipo')->options(['credit' => 'Crédito / Entrada', 'debit' => 'Débito / Saída'])->required()->native(false),
            TextInput::make('amount')->label('Valor')->prefix('R$')->numeric()->minValue(.01)->required(),
            TextInput::make('description')->label('Histórico')->required()->columnSpanFull(),
            TextInput::make('counterparty')->label('Favorecido / Cliente'),
            TextInput::make('reference')->label('Documento / Referência'),
            Select::make('category_id')->label('Categoria (Plano de Contas)')->relationship('category', 'descricao')->searchable()->preload()->required(),
            Textarea::make('notes')->label('Observações')->columnSpanFull(),
        ])]);
    }
}
