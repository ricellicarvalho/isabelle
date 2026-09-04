<?php

namespace App\Filament\Resources\BankAccounts\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;
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
                                        TextInput::make('nome')->label('Nome da conta')->placeholder('Ex.: Sicoob Movimento')->required()->maxLength(100),

                                        Select::make('tipo')->label('Tipo')->options([
                                            'conta_corrente' => 'Conta corrente', 'poupanca' => 'Poupança', 'caixa' => 'Caixa',
                                            'investimento' => 'Investimento', 'outra' => 'Outra',
                                        ])->default('conta_corrente')->required()->native(false),

                                        Select::make('banco')
                                            ->label('Banco')
                                            ->options([
                                                '001' => '001 - Banco do Brasil',
                                                '033' => '033 - Santander',
                                                '104' => '104 - Caixa Econômica',
                                                '237' => '237 - Bradesco',
                                                '341' => '341 - Itaú',
                                                '756' => '756 - Sicoob',
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

                                        DatePicker::make('opening_balance_date')->label('Data do saldo inicial')->default('2026-07-31')->required()->native(false)->displayFormat('d/m/Y'),
                                        TextInput::make('opening_balance')->label('Saldo inicial')->numeric()->prefix('R$')->default(0)->required(),
                                        TextInput::make('credit_limit')->label('Limite bancário')->numeric()->prefix('R$')->default(0),

                                        Toggle::make('ativo')->label('Conta ativa')->default(true),

                                        Toggle::make('uses_billing')->label('Utiliza para emissão de boletos')->live()->columnSpanFull(),
                                        Toggle::make('is_default_billing')->label('Conta padrão para boletos')->visible(fn (Get $get): bool => (bool) $get('uses_billing')),

                                        TextInput::make('carteira')
                                            ->label('Carteira')
                                            ->required(fn (Get $get): bool => (bool) $get('uses_billing'))
                                            ->visible(fn (Get $get): bool => (bool) $get('uses_billing'))
                                            ->maxLength(10)
                                            ->helperText('Bradesco: 06, 09, 16, 19, 21, 22'),

                                        TextInput::make('convenio')->label('Convênio / Código Cedente')->maxLength(30)->visible(fn (Get $get): bool => (bool) $get('uses_billing')),
                                    ]),
                            ]),

                        Tab::make('Cedente')
                            ->icon(Heroicon::User)
                            ->visible(fn (Get $get): bool => (bool) $get('uses_billing'))->components([
                                Section::make('Dados do Cedente')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('cedente_nome')->label('Razão Social')->required(fn (Get $get): bool => (bool) $get('uses_billing'))->maxLength(255)->columnSpanFull(),
                                        TextInput::make('cedente_documento')
                                            ->label('CNPJ')
                                            ->required(fn (Get $get): bool => (bool) $get('uses_billing'))
                                            ->maxLength(18)
                                            ->helperText('Pode informar com ou sem formatação (ex: 58.953.530/0001-00 ou 58953530000100)'),
                                        TextInput::make('cedente_endereco')->label('Endereço')->maxLength(255),
                                        TextInput::make('cedente_cidade_uf')->label('Cidade/UF')->maxLength(255),
                                        FileUpload::make('logo_path')
                                            ->label('Logo da Empresa (para o boleto)')
                                            ->image()
                                            ->disk('public')
                                            ->directory('logos')
                                            ->acceptedFileTypes(['image/png', 'image/jpeg'])
                                            ->maxSize(1024)
                                            ->helperText('PNG ou JPG, máx. 1 MB. Aparece no cabeçalho do boleto PDF.')
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Configuração')
                            ->icon(Heroicon::Cog)
                            ->visible(fn (Get $get): bool => (bool) $get('uses_billing'))->components([
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
