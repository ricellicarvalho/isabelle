<?php

namespace App\Filament\Resources\NfseServiceCodes\Schemas;

use Filament\Forms\Components\Select;
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
                ->components([
                    Select::make('tipo_servico')
                        ->label('Tipo de Serviço (Sistema)')
                        ->required()
                        ->native(false)
                        ->options([
                            'nr1'          => 'NR-1 (Consultoria Psicossocial)',
                            'palestra'     => 'Palestra',
                            'consultoria'  => 'Consultoria',
                            'treinamento'  => 'Treinamento',
                            'outro'        => 'Outro',
                        ])
                        ->helperText('Corresponde ao tipo de serviço do Contrato'),

                    TextInput::make('descricao')
                        ->label('Descrição do Serviço')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('item_lista_servico')
                        ->label('Item Lista Serviço (LC 116/2003)')
                        ->required()
                        ->maxLength(10)
                        ->placeholder('17.01')
                        ->helperText('17.01=Consultoria, 8.02=Instrução/Treinamento'),

                    TextInput::make('aliquota')
                        ->label('Alíquota ISS (%)')
                        ->required()
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(2.00)
                        ->suffix('%'),

                    TextInput::make('codigo_tributacao_municipio')
                        ->label('Código Tributação Municipal')
                        ->maxLength(20)
                        ->placeholder('Conforme tabela da prefeitura'),

                    TextInput::make('codigo_cnae')
                        ->label('Código CNAE')
                        ->maxLength(10)
                        ->placeholder('7020400'),

                    Toggle::make('ativo')
                        ->label('Ativo')
                        ->default(true),
                ]),
        ]);
    }
}
