<?php

namespace App\Filament\Resources\BankAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('banco')
                    ->label('Banco')
                    ->formatStateUsing(fn ($record) => "{$record->banco} - {$record->banco_nome}")
                    ->searchable(),

                TextColumn::make('agencia')->label('Agência')->formatStateUsing(fn ($record) => $record->agencia . ($record->agencia_dv ? "-{$record->agencia_dv}" : '')),

                TextColumn::make('conta')->label('Conta')->formatStateUsing(fn ($record) => $record->conta . ($record->conta_dv ? "-{$record->conta_dv}" : '')),

                TextColumn::make('carteira')->label('Carteira'),

                TextColumn::make('layout_remessa')->label('Layout')->badge(),

                TextColumn::make('proximo_nosso_numero')->label('Próx. NN')->numeric(),

                IconColumn::make('ativo')->label('Ativo')->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
