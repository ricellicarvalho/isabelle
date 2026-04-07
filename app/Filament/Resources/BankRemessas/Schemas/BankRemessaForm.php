<?php

namespace App\Filament\Resources\BankRemessas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankRemessaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Dados da Remessa')
                    ->columns(2)
                    ->components([
                        TextInput::make('sequencial_arquivo')->label('Sequencial')->disabled(),
                        TextInput::make('layout')->label('Layout')->disabled(),
                        TextInput::make('data_geracao')->label('Geração')->disabled(),
                        TextInput::make('quantidade_titulos')->label('Qtd. Títulos')->disabled(),
                        TextInput::make('valor_total')->label('Valor Total')->prefix('R$')->disabled(),
                        TextInput::make('caminho_arquivo')->label('Arquivo')->disabled()->columnSpanFull(),
                    ]),
            ]);
    }
}
