<?php

namespace App\Filament\Resources\NfseServiceCodes\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NfseServiceCodesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('tipo_servico')
                    ->label('Tipo (Sistema)')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nr1'          => 'primary',
                        'palestra'     => 'info',
                        'consultoria'  => 'warning',
                        'treinamento'  => 'success',
                        default        => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'nr1'          => 'NR-1',
                        'palestra'     => 'Palestra',
                        'consultoria'  => 'Consultoria',
                        'treinamento'  => 'Treinamento',
                        'outro'        => 'Outro',
                    }),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(40),

                TextColumn::make('item_lista_servico')
                    ->label('LC 116/2003'),

                TextColumn::make('aliquota')
                    ->label('ISS %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%'),

                TextColumn::make('codigo_cnae')
                    ->label('CNAE')
                    ->placeholder('—'),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
