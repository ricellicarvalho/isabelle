<?php

namespace App\Filament\Resources\BankRemessas\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BoletosRelationManager extends RelationManager
{
    protected static string $relationship = 'boletos';

    protected static ?string $title = 'Boletos da Remessa';

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
                TextColumn::make('receivable.client.razao_social')->label('Cliente')->limit(30),
                TextColumn::make('valor')->label('Valor')->money('BRL'),
                TextColumn::make('data_vencimento')->label('Vencimento')->date('d/m/Y'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
