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
                    ->label('Código')
                    ->badge()
                    ->color('primary')
                    ->searchable(),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(60)
                    ->searchable()
                    ->tooltip(fn (string $state): string => $state),

                TextColumn::make('item_lista_servico')
                    ->label('LC 116/2003')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('codigo_tributacao_nacional')
                    ->label('cTribNac')
                    ->badge()
                    ->color(fn ($state) => blank($state) ? 'danger' : 'success')
                    ->placeholder('⚠ não configurado'),

                TextColumn::make('codigo_tributacao_municipio')
                    ->label('cTribMun')
                    ->placeholder('—'),

                TextColumn::make('aliquota')
                    ->label('ISS %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%'),

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
