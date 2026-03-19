<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Icons\Heroicon;

// AS NOVAS IMPORTAÇÕES DA V5:
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup; 
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\QueryBuilder;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                // Coluna de Nome com busca e ordenação
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                // Coluna de E-mail
                TextColumn::make('email')
                    ->label('E-mail')
                    //->limit(20)
                    ->searchable()
                    ->sortable(),

                // Coluna de Telefone (opcional)
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),
                    //->toggleable(isToggledHiddenByDefault: true), // Fica escondido por padrão, mas pode ser ativado

                TextColumn::make('is_admin')
                ->label('Admin?')
				->badge()				
                ->color(fn ($state) => $state === 1 ? 'success' : 'red')
				->formatStateUsing(fn ($state) => match ($state) {
					1 => 'Sim',
					0 => 'Não',
				}),                                

                // Data de criação formatada
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                // Data de alteração formatada
                TextColumn::make('updated_at')
                    ->label('Alterado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])->deferColumnManager(false) // Remove o botão Aplicar colunas
            ->filters([
                Filter::make('is_admin')->toggle()->label('Admin?')->query(fn (Builder $query): Builder => $query->where('is_admin', true)),
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
