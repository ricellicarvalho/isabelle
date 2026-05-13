<?php

namespace App\Filament\Resources\ClientDocuments\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ClientDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('client.razao_social')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('titulo')
                    ->label('Título')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'laudo'        => 'Laudo',
                        'foto'         => 'Foto',
                        'relatorio'    => 'Relatório',
                        'matriz_risco' => 'Matriz de Risco',
                        'certificado'  => 'Certificado',
                        'proposta'     => 'Proposta',
                        default        => 'Outro',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'proposta' => 'warning',
                        default    => 'gray',
                    }),

                IconColumn::make('visivel_portal')
                    ->label('Portal')
                    ->boolean()
                    ->trueIcon('heroicon-o-eye')
                    ->falseIcon('heroicon-o-eye-slash'),

                TextColumn::make('created_at')
                    ->label('Adicionado em')
                    ->date('d/m/Y')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(function (): array {
                        $options = [
                            'laudo'        => 'Laudo',
                            'foto'         => 'Foto',
                            'relatorio'    => 'Relatório',
                            'matriz_risco' => 'Matriz de Risco',
                            'certificado'  => 'Certificado',
                            'outro'        => 'Outro',
                        ];
                        if (auth()->user()?->hasAnyRole(['super_admin', 'administrador', 'financeiro'])) {
                            $options['proposta'] = 'Proposta';
                        }
                        return $options;
                    }),

                TernaryFilter::make('visivel_portal')
                    ->label('Visível no Portal'),

                SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'razao_social')
                    ->searchable()
                    ->preload(),
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
