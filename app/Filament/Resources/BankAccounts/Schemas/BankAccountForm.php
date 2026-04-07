<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class BankAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Banco')
                            ->icon(Heroicon::BuildingLibrary)
                            ->components([
                                Section::make('Identificação Bancária')
                                    ->columns(2)
                                    ->components([
                                        Select::make('banco')
                                            ->label('Banco')
                                            ->options([
                                                '001' => '001 - Banco do Brasil',
                                                '033' => '033 - Santander',
                                                '104' => '104 - Caixa Econômica',
                                                '237' => '237 - Bradesco',
                                                '341' => '341 - Itaú',
                                            ])
                                            ->required()
                                            ->native(false),

                                        TextInput::make('descricao')
                                            ->label('Descrição (opcional)')
                                            ->maxLength(255),

                                        TextInput::make('agencia')->label('Agência')->required()->maxLength(10),
                                        TextInput::make('agencia_dv')->label('DV Agência')->maxLength(2),

                                        TextInput::make('conta')->label('Conta')->required()->maxLength(20),
                                        TextInput::make('conta_dv')->label('DV Conta')->maxLength(2),

                                        TextInput::make('carteira')
                                            ->label('Carteira')
                                            ->required()
                                            ->maxLength(10)
                                            ->helperText('Bradesco: 06, 09, 16, 19, 21, 22'),

                                        TextInput::make('convenio')->label('Convênio / Código Cedente')->maxLength(30),
                                    ]),
                            ]),

                        Tab::make('Cedente')
                            ->icon(Heroicon::User)
                            ->components([
                                Section::make('Dados do Cedente')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('cedente_nome')->label('Razão Social')->required()->maxLength(255)->columnSpanFull(),
                                        TextInput::make('cedente_documento')->label('CNPJ')->required()->maxLength(18),
                                        TextInput::make('cedente_endereco')->label('Endereço')->maxLength(255),
                                        TextInput::make('cedente_cidade_uf')->label('Cidade/UF')->maxLength(255),
                                    ]),
                            ]),

                        Tab::make('Configuração')
                            ->icon(Heroicon::Cog)
                            ->components([
                                Section::make('Remessa & Sequenciais')
                                    ->columns(2)
                                    ->components([
                                        Select::make('layout_remessa')
                                            ->label('Layout CNAB')
                                            ->options([
                                                '400' => 'CNAB 400',
                                                '240' => 'CNAB 240',
                                            ])
                                            ->default('400')
                                            ->required()
                                            ->native(false),

                                        Toggle::make('ativo')->label('Ativo')->default(true),

                                        TextInput::make('proximo_nosso_numero')
                                            ->label('Próximo Nosso Número')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required(),

                                        TextInput::make('proximo_sequencial_remessa')
                                            ->label('Próximo Sequencial de Remessa')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->required(),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }
}
