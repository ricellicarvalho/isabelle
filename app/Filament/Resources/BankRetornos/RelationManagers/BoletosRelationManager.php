<?php

namespace App\Filament\Resources\BankRetornos\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BoletosRelationManager extends RelationManager
{
    protected static string $relationship = 'boletos';

    protected static ?string $title = 'Boletos atualizados';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nosso_numero')
            ->columns([
                TextColumn::make('nosso_numero')->label('Nosso Número')->searchable(),
                TextColumn::make('receivable.descricao')->label('Parcela')->limit(40),
                TextColumn::make('valor')->label('Valor')->money('BRL'),
                TextColumn::make('valor_pago')->label('Valor Pago')->money('BRL')->placeholder('—'),
                TextColumn::make('data_pagamento')->label('Pago em')->date('d/m/Y')->placeholder('—'),
                TextColumn::make('status')->label('Status')->badge(),
            ]);
    }
}
