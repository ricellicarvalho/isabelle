<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    private const ROLE_LABELS = [
        'super_admin'        => 'Super Admin',
        'administrador'      => 'Administrador',
        'financeiro'         => 'Financeiro',
        'colaborador'        => 'Colaborador',
        'seguranca_trabalho' => 'Segurança de Trabalho',
        'cliente'            => 'Cliente',
    ];

    private const ROLE_COLORS = [
        'super_admin'        => 'danger',
        'administrador'      => 'warning',
        'financeiro'         => 'success',
        'colaborador'        => 'info',
        'seguranca_trabalho' => 'primary',
        'cliente'            => 'success',
    ];

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('E-mail')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Telefone')
                    ->searchable(),

                TextColumn::make('roles.name')
                    ->label('Perfil')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => self::ROLE_LABELS[$state] ?? $state)
                    ->color(fn ($state): string => self::ROLE_COLORS[$state] ?? 'gray'),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Alterado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferColumnManager(false)
            ->filters([
                Filter::make('sem_perfil')
                    ->label('Sem perfil atribuído')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('roles')),
            ])
            ->actions([
                EditAction::make()
                    ->visible(fn ($record): bool => ! ($record->hasRole('super_admin') && ! auth()->user()?->hasRole('super_admin'))),
                DeleteAction::make()
                    ->visible(fn ($record): bool => ! ($record->hasRole('super_admin') && ! auth()->user()?->hasRole('super_admin'))),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
