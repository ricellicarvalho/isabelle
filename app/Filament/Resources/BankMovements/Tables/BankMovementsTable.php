<?php

namespace App\Filament\Resources\BankMovements\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class BankMovementsTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('occurred_at', 'desc')->columns([
            TextColumn::make('occurred_at')->label('Lançamento')->dateTime('d/m/Y H:i')->sortable(),
            TextColumn::make('bankAccount.nome')->label('Conta')->searchable(),
            TextColumn::make('reference')->label('Número')->placeholder('—')->searchable(),
            TextColumn::make('description')->label('Histórico')->searchable()->wrap(),
            TextColumn::make('counterparty')->label('Favorecido')->placeholder('—')->searchable(),
            TextColumn::make('category.descricao')->label('Categoria')->placeholder('Transferência'),
            TextColumn::make('amount')->label('Valor')->money('BRL')->color(fn ($record) => $record->direction === 'credit' ? 'success' : 'danger')->formatStateUsing(fn ($state, $record) => ($record->direction === 'debit' ? '- ' : '+ ').'R$ '.number_format((float) $state, 2, ',', '.')),
            TextColumn::make('status')->label('Situação')->badge()->formatStateUsing(fn ($state) => $state === 'confirmed' ? 'Confirmado' : ucfirst($state)),
        ])->filters([
            SelectFilter::make('bank_account_id')->label('Conta')->relationship('bankAccount', 'nome'),
            SelectFilter::make('direction')->label('Tipo')->options(['credit' => 'Crédito', 'debit' => 'Débito']),
        ])->actions([EditAction::make()->visible(fn ($record) => $record->origin === 'manual')]);
    }
}
