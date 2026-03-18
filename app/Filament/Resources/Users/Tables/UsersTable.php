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
                    ->searchable(),

                // Coluna de Telefone (opcional)
                TextColumn::make('phone')
                    ->label('Telefone')
                    ->toggleable(isToggledHiddenByDefault: true), // Fica escondido por padrão, mas pode ser ativado

                // Ícone para mostrar se é Admin
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean() // Transforma true/false em ícones de check/x
                    ->sortable(),

                // Data de criação formatada
                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Aqui você pode adicionar filtros, ex: Apenas Admins
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
