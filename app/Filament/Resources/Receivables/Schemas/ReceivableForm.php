<?php

namespace App\Filament\Resources\Receivables\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReceivableForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('tabs')
                    ->tabs([
                        Tab::make('Identificação')
                            ->icon(Heroicon::DocumentText)
                            ->components([
                                Section::make('Vínculos')
                                    ->columns(2)
                                    ->components([
                                        Select::make('client_id')
                                            ->label('Cliente')
                                            ->relationship('client', 'razao_social')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->native(false),

                                        Select::make('contract_id')
                                            ->label('Contrato')
                                            ->relationship('contract', 'numero')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Avulso (sem contrato)')
                                            ->native(false),

                                        SelectTree::make('category_id')
                                            ->label('Categoria (Plano de Contas)')
                                            ->relationship('category', 'descricao', 'parent_id')
                                            ->searchable()
                                            ->required()
                                            ->columnSpanFull(),

                                        TextInput::make('descricao')
                                            ->label('Descrição')
                                            ->required()
                                            ->maxLength(255)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Valores')
                            ->icon(Heroicon::CurrencyDollar)
                            ->components([
                                Section::make('Valores')
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('valor')
                                            ->label('Valor')
                                            ->required()
                                            ->numeric()
                                            ->prefix('R$')
                                            ->minValue(0),

                                        TextInput::make('numero_parcela')
                                            ->label('Número da Parcela')
                                            ->numeric()
                                            ->default(1)
                                            ->minValue(1),

                                        TextInput::make('valor_pago')
                                            ->label('Valor Pago')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->minValue(0),
                                    ]),
                            ]),

                        Tab::make('Pagamento')
                            ->icon(Heroicon::CalendarDays)
                            ->components([
                                Section::make('Vencimento e Pagamento')
                                    ->columns(2)
                                    ->components([
                                        DatePicker::make('data_vencimento')
                                            ->label('Data de Vencimento')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),

                                        DatePicker::make('data_pagamento')
                                            ->label('Data de Pagamento')
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),

                                        Select::make('forma_pagamento')
                                            ->label('Forma de Pagamento')
                                            ->options([
                                                'boleto' => 'Boleto',
                                                'pix' => 'PIX',
                                                'transferencia' => 'Transferência',
                                                'dinheiro' => 'Dinheiro',
                                                'cartao' => 'Cartão',
                                            ])
                                            ->native(false),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options([
                                                'pendente' => 'Pendente',
                                                'pago' => 'Pago',
                                                'cancelado' => 'Cancelado',
                                                'vencido' => 'Vencido',
                                            ])
                                            ->default('pendente')
                                            ->required()
                                            ->native(false),
                                    ]),

                                Section::make('Observações')
                                    ->components([
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->rows(3)
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
