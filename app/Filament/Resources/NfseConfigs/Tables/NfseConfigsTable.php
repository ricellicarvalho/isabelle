<?php

namespace App\Filament\Resources\NfseConfigs\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NfseConfigsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('razao_social')
                    ->label('Razão Social')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('cnpj')
                    ->label('CNPJ')
                    ->formatStateUsing(fn ($state): string =>
                        preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $state)
                    ),

                TextColumn::make('inscricao_municipal')
                    ->label('Insc. Municipal'),

                TextColumn::make('nome_municipio')
                    ->label('Município')
                    ->formatStateUsing(fn ($record): string => "{$record->nome_municipio}-{$record->uf}"),

                TextColumn::make('aliquota_iss_padrao')
                    ->label('ISS %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%'),

                IconColumn::make('padrao_nacional')
                    ->label('Padrão Nacional')
                    ->boolean(),

                IconColumn::make('ativo')
                    ->label('Ativo')
                    ->boolean(),

                TextColumn::make('proximo_numero_rps')
                    ->label('Próx. RPS')
                    ->alignCenter()
                    ->sortable(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
