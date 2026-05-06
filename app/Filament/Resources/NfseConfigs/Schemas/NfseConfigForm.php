<?php

namespace App\Filament\Resources\NfseConfigs\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class NfseConfigForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Tabs::make('tabs')
                ->tabs([
                    Tab::make('Prestador')
                        ->icon(Heroicon::BuildingOffice2)
                        ->components([
                            Section::make('Identificação')
                                ->columns(2)
                                ->components([
                                    TextInput::make('cnpj')
                                        ->label('CNPJ')
                                        ->required()
                                        ->maxLength(14)
                                        ->helperText('Apenas números, sem formatação')
                                        ->columnSpanFull(),

                                    TextInput::make('razao_social')
                                        ->label('Razão Social')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    TextInput::make('nome_fantasia')
                                        ->label('Nome Fantasia')
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    TextInput::make('inscricao_municipal')
                                        ->label('Inscrição Municipal')
                                        ->required()
                                        ->maxLength(20),

                                    TextInput::make('email')
                                        ->label('E-mail')
                                        ->email()
                                        ->required()
                                        ->maxLength(255),

                                    TextInput::make('telefone')
                                        ->label('Telefone')
                                        ->tel()
                                        ->maxLength(20),
                                ]),

                            Section::make('Endereço')
                                ->columns(2)
                                ->components([
                                    TextInput::make('cep')
                                        ->label('CEP')
                                        ->required()
                                        ->maxLength(8)
                                        ->helperText('Apenas números'),

                                    TextInput::make('uf')
                                        ->label('UF')
                                        ->required()
                                        ->maxLength(2)
                                        ->placeholder('TO'),

                                    TextInput::make('endereco')
                                        ->label('Logradouro')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    TextInput::make('numero')
                                        ->label('Número')
                                        ->required()
                                        ->maxLength(20),

                                    TextInput::make('complemento')
                                        ->label('Complemento')
                                        ->maxLength(100),

                                    TextInput::make('bairro')
                                        ->label('Bairro')
                                        ->required()
                                        ->maxLength(100),

                                    TextInput::make('nome_municipio')
                                        ->label('Município')
                                        ->required()
                                        ->maxLength(100)
                                        ->placeholder('Gurupi'),

                                    TextInput::make('municipio_ibge')
                                        ->label('Código IBGE do Município')
                                        ->required()
                                        ->maxLength(7)
                                        ->placeholder('1709500')
                                        ->helperText('7 dígitos — ex: 1709500 para Gurupi-TO'),

                                    TextInput::make('codigo_uf')
                                        ->label('Código UF (2 dígitos)')
                                        ->required()
                                        ->maxLength(2)
                                        ->placeholder('17')
                                        ->helperText('17 = Tocantins'),
                                ]),
                        ]),

                    Tab::make('Tributação')
                        ->icon(Heroicon::ReceiptPercent)
                        ->components([
                            Section::make('Regime e ISS')
                                ->columns(2)
                                ->components([
                                    Select::make('simples_nacional')
                                        ->label('Regime Tributário')
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            '1' => 'Simples Nacional',
                                            '2' => 'Lucro Presumido / Real',
                                        ])
                                        ->default('2'),

                                    Select::make('regime_especial_tributacao')
                                        ->label('Regime Especial')
                                        ->native(false)
                                        ->options([
                                            '1' => '1 — Microempresa Municipal',
                                            '2' => '2 — Estimativa',
                                            '3' => '3 — Sociedade de Profissionais',
                                            '4' => '4 — Cooperativa',
                                            '5' => '5 — MEI — Simples Nacional',
                                            '6' => '6 — ME e EPP — Simples Nacional',
                                        ])
                                        ->placeholder('Nenhum'),

                                    TextInput::make('aliquota_iss_padrao')
                                        ->label('Alíquota ISS Padrão (%)')
                                        ->required()
                                        ->numeric()
                                        ->minValue(0)
                                        ->maxValue(100)
                                        ->default(2.00)
                                        ->suffix('%'),

                                    Select::make('iss_retido')
                                        ->label('ISS Retido pelo Tomador?')
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            '1' => 'Sim — Retido',
                                            '2' => 'Não — Não retido',
                                        ])
                                        ->default('2')
                                        ->live(),

                                    Select::make('responsavel_retencao')
                                        ->label('Responsável pela Retenção do ISS')
                                        ->native(false)
                                        ->options([
                                            '1' => '1 — Emitente (Prestador)',
                                            '2' => '2 — Tomador',
                                            '3' => '3 — Intermediário do Serviço',
                                        ])
                                        ->default('2')
                                        ->visible(fn ($get) => $get('iss_retido') === '1')
                                        ->helperText('Enviado somente quando ISS é retido. Valores ABRASF: 1, 2 ou 3.'),

                                    Select::make('exigibilidade_iss')
                                        ->label('Exigibilidade do ISS')
                                        ->required()
                                        ->native(false)
                                        ->options([
                                            '1' => '1 — Exigível',
                                            '2' => '2 — Não incidência',
                                            '3' => '3 — Isenção',
                                            '4' => '4 — Exportação',
                                            '5' => '5 — Imunidade',
                                            '6' => '6 — Susp. Jud. do ISS',
                                            '7' => '7 — Susp. Adm. do ISS',
                                        ])
                                        ->default('1'),
                                ]),

                            Section::make('Codificação do Serviço Padrão')
                                ->columns(2)
                                ->description('Usado quando não há código específico cadastrado para o tipo de serviço do contrato')
                                ->components([
                                    TextInput::make('item_lista_servico')
                                        ->label('Item Lista Serviço (LC 116/2003)')
                                        ->required()
                                        ->maxLength(10)
                                        ->default('17.01')
                                        ->placeholder('17.01')
                                        ->helperText('Ex: 17.01=Consultoria, 8.02=Treinamento'),

                                    TextInput::make('codigo_tributacao_municipio')
                                        ->label('Código Tributação Municipal')
                                        ->maxLength(20)
                                        ->placeholder('Conforme tabela da prefeitura'),

                                    TextInput::make('codigo_cnae')
                                        ->label('Código CNAE')
                                        ->maxLength(10)
                                        ->placeholder('6209100'),
                                ]),
                        ]),

                    Tab::make('RPS / Config')
                        ->icon(Heroicon::Cog)
                        ->components([
                            Section::make('Configurações de Emissão')
                                ->columns(2)
                                ->components([
                                    Toggle::make('padrao_nacional')
                                        ->label('Padrão Nacional NFSe')
                                        ->default(true)
                                        ->helperText('Marque se o município aderiu ao padrão nacional de NFSe'),

                                    Toggle::make('ativo')
                                        ->label('Ativo')
                                        ->default(true),

                                    TextInput::make('serie_rps')
                                        ->label('Série RPS')
                                        ->required()
                                        ->maxLength(5)
                                        ->default('RPS'),

                                    TextInput::make('proximo_numero_rps')
                                        ->label('Próximo Número RPS')
                                        ->required()
                                        ->numeric()
                                        ->minValue(1)
                                        ->default(1)
                                        ->helperText('Contador sequencial — incrementado automaticamente a cada emissão'),
                                ]),

                            Section::make('Credenciais Municipais')
                                ->description('Preencha apenas se a prefeitura exigir login no portal NFSe (ex: SIGISS, ISS.net, Governa). Deixe em branco se usar apenas o certificado A1.')
                                ->icon(Heroicon::Key)
                                ->columns(2)
                                ->collapsible()
                                ->collapsed()
                                ->components([
                                    TextInput::make('usuario_prefeitura')
                                        ->label('Usuário (Portal Prefeitura)')
                                        ->maxLength(100)
                                        ->placeholder('CPF ou login do responsável'),

                                    TextInput::make('senha_prefeitura')
                                        ->label('Senha (Portal Prefeitura)')
                                        ->password()
                                        ->revealable()
                                        ->maxLength(100),

                                    TextInput::make('frase_secreta')
                                        ->label('Frase Secreta')
                                        ->maxLength(100)
                                        ->helperText('Exigida por alguns municípios'),

                                    TextInput::make('chave_acesso')
                                        ->label('Chave de Acesso')
                                        ->maxLength(255)
                                        ->helperText('Token ou chave fornecida pela prefeitura'),

                                    TextInput::make('chave_autorizacao')
                                        ->label('Chave de Autorização')
                                        ->maxLength(255)
                                        ->columnSpanFull(),
                                ]),
                        ]),
                ])
                ->activeTab(1)
                ->contained(false)
                ->columnSpanFull(),
        ]);
    }
}
