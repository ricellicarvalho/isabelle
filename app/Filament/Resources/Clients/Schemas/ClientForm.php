<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
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
                                Section::make('Checklist de Conformidade NR-1')
                                    ->description('RN07: O status "Regularizada" é definido automaticamente quando todos os itens estão concluídos.')
                                    ->columns(1)
                                    ->components([
                                        Checkbox::make('nr1_checklist.avaliacao')
                                            ->label('✅ Avaliação Psicossocial realizada')
                                            ->live(),

                                        Checkbox::make('nr1_checklist.devolutiva')
                                            ->label('✅ Devolutiva entregue ao cliente')
                                            ->live(),

                                        Checkbox::make('nr1_checklist.plano')
                                            ->label('✅ Plano de Ação elaborado')
                                            ->live(),

                                        Checkbox::make('nr1_checklist.treinamento')
                                            ->label('✅ Treinamento realizado')
                                            ->live(),

                                        Checkbox::make('nr1_checklist.relatorio')
                                            ->label('✅ Relatório Final entregue')
                                            ->live(),

                                        Placeholder::make('nr1_progresso')
                                            ->label('Progresso')
                                            ->content(function (Get $get): string {
                                                $itens = ['avaliacao', 'devolutiva', 'plano', 'treinamento', 'relatorio'];
                                                $checklist = $get('nr1_checklist') ?? [];
                                                $done = count(array_filter($itens, fn ($i) => ! empty($checklist[$i])));
                                                $pct = (int) round(($done / count($itens)) * 100);

                                                return "{$done}/5 itens concluídos ({$pct}%)";
                                            }),
                                    ]),

                                Section::make('Status NR-1')
                                    ->columns(1)
                                    ->components([
                                        Select::make('nr1_status')
                                            ->label('Status NR-1')
                                            ->options([
                                                'pendente'     => 'Pendente',
                                                'em_andamento' => 'Em Andamento',
                                                'regularizada' => 'Regularizada',
                                            ])
                                            ->default('pendente')
                                            ->native(false)
                                            ->helperText('Atualizado automaticamente conforme o checklist.'),
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
