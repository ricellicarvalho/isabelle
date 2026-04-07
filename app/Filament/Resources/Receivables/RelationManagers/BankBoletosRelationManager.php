<?php

namespace App\Filament\Resources\Receivables\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BankBoletosRelationManager extends RelationManager
{
    protected static string $relationship = 'bankBoletos';

    protected static ?string $title = 'Boletos';

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
                TextColumn::make('carteira')->label('Carteira')->placeholder('—'),
                TextColumn::make('valor')->label('Valor')->money('BRL'),
                TextColumn::make('data_vencimento')->label('Vencimento')->date('d/m/Y'),
                TextColumn::make('remessa.sequencial_arquivo')->label('Remessa')->placeholder('—'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'emitido' => 'info',
                        'pago' => 'success',
                        'cancelado' => 'gray',
                    }),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
