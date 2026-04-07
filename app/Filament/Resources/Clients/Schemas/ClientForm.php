<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Dados Cadastrais')
                            ->icon(Heroicon::BuildingOffice)
                            ->components([
                                Section::make('Identificação')
                                    ->columns(2)
                                    ->components([
                                        Select::make('tipo_pessoa')
                                            ->label('Tipo de Pessoa')
                                            ->options([
                                                'pj' => 'Pessoa Jurídica',
                                                'pf' => 'Pessoa Física',
                                            ])
                                            ->default('pj')
                                            ->required()
                                            ->native(false),

                                        TextInput::make('cnpj_cpf')
                                            ->label('CNPJ/CPF')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(18),

                                        TextInput::make('razao_social')
                                            ->label('Razão Social')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),

                                        TextInput::make('nome_fantasia')
                                            ->label('Nome Fantasia')
                                            ->maxLength(255),

                                        TextInput::make('inscricao_estadual')
                                            ->label('Inscrição Estadual')
                                            ->maxLength(255),

                                        TextInput::make('inscricao_municipal')
                                            ->label('Inscrição Municipal')
                                            ->maxLength(255),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'ativo' => 'Ativo',
                                                'inativo' => 'Inativo',
                                            ])
                                            ->default('ativo')
                                            ->required()
                                            ->native(false),
                                    ]),
                            ]),

                        Tab::make('Endereço')
                            ->icon(Heroicon::MapPin)
                            ->components([
                                Section::make('Endereço')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('cep')
                                            ->label('CEP')
                                            ->maxLength(10),

                                        TextInput::make('endereco')
                                            ->label('Logradouro')
                                            ->maxLength(255),

                                        TextInput::make('numero')
                                            ->label('Número')
                                            ->maxLength(20),

                                        TextInput::make('complemento')
                                            ->label('Complemento')
                                            ->maxLength(255),

                                        TextInput::make('bairro')
                                            ->label('Bairro')
                                            ->maxLength(255),

                                        TextInput::make('cidade')
                                            ->label('Cidade')
                                            ->maxLength(255),

                                        Select::make('uf')
                                            ->label('UF')
                                            ->options(self::ufs())
                                            ->searchable()
                                            ->native(false),
                                    ]),
                            ]),

                        Tab::make('Contato')
                            ->icon(Heroicon::Phone)
                            ->components([
                                Section::make('Informações de Contato')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('telefone')
                                            ->label('Telefone')
                                            ->tel()
                                            ->maxLength(20),

                                        TextInput::make('email')
                                            ->label('E-mail')
                                            ->email()
                                            ->maxLength(255),

                                        TextInput::make('contato_nome')
                                            ->label('Nome do Contato')
                                            ->maxLength(255),

                                        TextInput::make('contato_telefone')
                                            ->label('Telefone do Contato')
                                            ->tel()
                                            ->maxLength(20),
                                    ]),
                            ]),

                        Tab::make('NR-1')
                            ->icon(Heroicon::ClipboardDocumentCheck)
                            ->components([
                                Section::make('Conformidade NR-1')
                                    ->columns(2)
                                    ->components([
                                        Select::make('nr1_status')
                                            ->label('Status NR-1')
                                            ->options([
                                                'pendente' => 'Pendente',
                                                'em_andamento' => 'Em Andamento',
                                                'regularizada' => 'Regularizada',
                                            ])
                                            ->default('pendente')
                                            ->native(false)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make('Observações')
                                    ->components([
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    private static function ufs(): array
    {
        return [
            'AC' => 'AC', 'AL' => 'AL', 'AP' => 'AP', 'AM' => 'AM',
            'BA' => 'BA', 'CE' => 'CE', 'DF' => 'DF', 'ES' => 'ES',
            'GO' => 'GO', 'MA' => 'MA', 'MT' => 'MT', 'MS' => 'MS',
            'MG' => 'MG', 'PA' => 'PA', 'PB' => 'PB', 'PR' => 'PR',
            'PE' => 'PE', 'PI' => 'PI', 'RJ' => 'RJ', 'RN' => 'RN',
            'RS' => 'RS', 'RO' => 'RO', 'RR' => 'RR', 'SC' => 'SC',
            'SP' => 'SP', 'SE' => 'SE', 'TO' => 'TO',
        ];
    }
}
