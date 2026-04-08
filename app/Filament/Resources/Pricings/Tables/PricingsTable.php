<?php

namespace App\Filament\Resources\Pricings\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PricingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('nome')
            ->columns([
                TextColumn::make('nome')
                    ->label('Serviço/Ação')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.descricao')
                    ->label('Categoria')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),

                TextColumn::make('custo_direto')
                    ->label('Custo Direto')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('custo_indireto')
                    ->label('Custo Indireto')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('margem_lucro')
                    ->label('Margem')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%')
                    ->sortable(),

                TextColumn::make('preco_venda')
                    ->label('Preço de Venda')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('lucro')
                    ->label('Lucro por Venda')
                    ->state(fn ($record): float => (float) $record->preco_venda - (float) $record->custo_direto - (float) $record->custo_indireto)
                    ->money('BRL')
                    ->color('success'),
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
