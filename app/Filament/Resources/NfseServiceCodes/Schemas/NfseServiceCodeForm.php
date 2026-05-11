<?php

namespace App\Filament\Resources\NfseServiceCodes\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class NfseServiceCodeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Mapeamento de Serviço')
                ->columns(2)
                ->columnSpanFull()
                ->components([
                    TextInput::make('tipo_servico')
                        ->label('Código de Atividade (Prefeitura)')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('Ex: 0415, 0802, 1701')
                        ->helperText('Código de 4 dígitos conforme tabela da Prefeitura de Gurupi-TO')
                        ->columnSpanFull(),

                    TextInput::make('descricao')
                        ->label('Descrição do Serviço')
                        ->required()
                        ->maxLength(500)
                        ->columnSpanFull(),

                    TextInput::make('item_lista_servico')
                        ->label('Item LC 116/2003')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('Ex: 4.15, 8.02, 17.01')
                        ->helperText('Código federal com ponto — derivado do código da prefeitura (0415 → 4.15)'),

                    TextInput::make('codigo_tributacao_municipio')
                        ->label('Código Tributação Municipal (cTribMun)')
                        ->maxLength(20)
                        ->placeholder('Ex: 415, 802, 701')
                        ->helperText('3 dígitos — padrão [0-9]{3} exigido pelo município'),

                    TextInput::make('codigo_tributacao_nacional')
                        ->label('Código Tributação Nacional (cTribNac)')
                        ->maxLength(6)
                        ->placeholder('Ex: 170101')
                        ->helperText('6 dígitos — obter com Geranet ou portal SPED NFS-e para cada serviço'),

                    TextInput::make('aliquota')
                        ->label('Alíquota ISS (%)')
                        ->required()
                        ->numeric()
                        ->step(0.01)
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(2.01)
                        ->suffix('%')
                        ->dehydrateStateUsing(fn ($state) => str_replace(',', '.', (string) $state))
                        ->helperText('Use ponto como separador decimal (ex: 2.01)'),

                    TextInput::make('codigo_cnae')
                        ->label('Código CNAE')
                        ->maxLength(10)
                        ->placeholder('Ex: 7020400'),

                    Toggle::make('ativo')
                        ->label('Ativo')
                        ->default(true),
                ]),
        ]);
    }
}
