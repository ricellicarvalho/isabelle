<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('codigo')
            ->columns([
                TextColumn::make('codigo')
                    ->label('Código')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('parent.descricao')
                    ->label('Conta Pai')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'receita' => 'success',
                        'custo' => 'warning',
                        'despesa' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'receita' => 'Receita',
                        'custo' => 'Custo',
                        'despesa' => 'Despesa',
                    }),

                TextColumn::make('order')
                    ->label('Ordem')
                    ->sortable(),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options([
                        'receita' => 'Receita',
                        'custo' => 'Custo',
                        'despesa' => 'Despesa',
                    ]),
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
