<?php

namespace App\Filament\Resources\BankRetornos\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BankRetornoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Arquivo de Retorno')
                ->columns(2)
                ->columnSpanFull()
                ->components([
                    TextInput::make('nome_arquivo')->label('Nome do Arquivo')->disabled(),
                    TextInput::make('banco')->label('Banco')->disabled(),
                    TextInput::make('layout')->label('Layout')->disabled(),
                    DatePicker::make('data_arquivo')->label('Data do Arquivo')->disabled(),
                    DatePicker::make('data_processamento')->label('Processado em')->disabled(),
                    TextInput::make('caminho_arquivo')->label('Caminho')->disabled()->columnSpanFull(),
                ]),

            Section::make('Totais')
                ->columns(3)
                ->columnSpanFull()
                ->components([
                    TextInput::make('quantidade_titulos')->label('Títulos')->disabled(),
                    TextInput::make('quantidade_liquidados')->label('Liquidados')->disabled(),
                    TextInput::make('quantidade_baixados')->label('Baixados')->disabled(),
                    TextInput::make('quantidade_entradas')->label('Entradas')->disabled(),
                    TextInput::make('quantidade_alterados')->label('Alterações')->disabled(),
                    TextInput::make('quantidade_erros')->label('Erros')->disabled(),
                    TextInput::make('quantidade_nao_encontrados')->label('Não encontrados')->disabled(),
                    TextInput::make('valor_total')->label('Valor Liquidado (R$)')->disabled(),
                ]),
        ]);
    }
}
