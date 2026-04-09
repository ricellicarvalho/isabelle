<?php

namespace App\Filament\Resources\Contracts\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;

class ContractForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Dados do Contrato')
                            ->icon(Heroicon::DocumentText)
                            ->components([
                                Section::make('Identificação')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('numero')
                                            ->label('Número do Contrato')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->maxLength(255),

                                        Select::make('client_id')
                                            ->label('Cliente')
                                            ->relationship('client', 'razao_social')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false),

                                        SelectTree::make('category_id')
                                            ->label('Categoria (Plano de Contas)')
                                            ->relationship('category', 'descricao', 'parent_id')
                                            ->searchable()
                                            ->required(),

                                        Select::make('tipo_servico')
                                            ->label('Tipo de Serviço')
                                            ->options([
                                                'nr1' => 'NR-1',
                                                'palestra' => 'Palestra',
                                                'consultoria' => 'Consultoria',
                                                'treinamento' => 'Treinamento',
                                                'outro' => 'Outro',
                                            ])
                                            ->default('nr1')
                                            ->required()
                                            ->native(false),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'rascunho' => 'Rascunho',
                                                'ativo' => 'Ativo',
                                                'finalizado' => 'Finalizado',
                                                'cancelado' => 'Cancelado',
                                            ])
                                            ->default('rascunho')
                                            ->required()
                                            ->native(false),

                                        Textarea::make('descricao')
                                            ->label('Descrição')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Financeiro')
                            ->icon(Heroicon::CurrencyDollar)
                            ->components([
                                Section::make('Valores e Pagamento')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('valor_total')
                                            ->label('Valor Total')
                                            ->required()
                                            ->prefix('R$')
                                            ->mask(RawJs::make('$money($input, \',\', \'.\', 2)'))
                                            ->stripCharacters('.')
                                            ->formatStateUsing(fn ($state) => is_numeric($state)
                                                ? number_format((float) $state, 2, ',', '.')
                                                : $state)
                                            ->dehydrateStateUsing(fn ($state) => filled($state)
                                                ? (float) str_replace(',', '.', $state)
                                                : null)
                                            ->rule('gte:0'),

                                        TextInput::make('quantidade_parcelas')
                                            ->label('Quantidade de Parcelas')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1)
                                            ->maxValue(120),

                                        Select::make('forma_pagamento')
                                            ->label('Forma de Pagamento')
                                            ->options([
                                                'boleto' => 'Boleto',
                                                'pix' => 'PIX',
                                                'transferencia' => 'Transferência',
                                                'dinheiro' => 'Dinheiro',
                                                'cartao' => 'Cartão',
                                            ])
                                            ->default('boleto')
                                            ->required()
                                            ->native(false),
                                    ]),
                            ]),

                        Tab::make('Vigência')
                            ->icon(Heroicon::CalendarDays)
                            ->components([
                                Section::make('Período de Vigência')
                                    ->columns(2)
                                    ->components([
                                        DatePicker::make('data_inicio')
                                            ->label('Data de Início')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),

                                        DatePicker::make('data_fim')
                                            ->label('Data de Fim')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->afterOrEqual('data_inicio'),
                                    ]),

                                Section::make('Anexo')
                                    ->components([
                                        FileUpload::make('arquivo_pdf')
                                            ->label('Arquivo PDF do Contrato')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->directory('contratos')
                                            ->maxSize(10240),
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
}
