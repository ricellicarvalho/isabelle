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
                    ->label('Serviço / Ação')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.descricao')
                    ->label('Categoria')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),

                TextColumn::make('num_funcionarios')
                    ->label('Func.')
                    ->numeric()
                    ->sortable()
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('margem_lucro')
                    ->label('Margem')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('custo_direto')
                    ->label('Custo Médio')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('preco_venda')
                    ->label('Total c/ Imposto')
                    ->money('BRL')
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('lucro_final')
                    ->label('Lucro Final')
                    ->state(fn ($record): float => (float) $record->preco_venda - (float) $record->custo_direto)
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
