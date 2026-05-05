<?php

namespace App\Filament\Resources\Payables\Schemas;

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

class PayableForm
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
                                Section::make('Dados da Conta')
                                    ->columns(2)
                                    ->components([
                                        Select::make('supplier_id')
                                            ->label('Fornecedor')
                                            ->relationship('supplier', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->native(false)
                                            ->createOptionForm([
                                                TextInput::make('nome')
                                                    ->label('Nome / Razão Social')
                                                    ->required()
                                                    ->maxLength(255),
                                                TextInput::make('cnpj_cpf')
                                                    ->label('CNPJ/CPF')
                                                    ->maxLength(18),
                                                TextInput::make('telefone')
                                                    ->label('Telefone')
                                                    ->tel()
                                                    ->maxLength(20),
                                                TextInput::make('email')
                                                    ->label('E-mail')
                                                    ->email()
                                                    ->maxLength(255),
                                            ])
                                            ->createOptionUsing(function (array $data): int {
                                                $data['created_by'] = auth()->id();

                                                return \App\Models\Supplier::create($data)->id;
                                            }),

                                        SelectTree::make('category_id')
                                            ->label('Categoria (Plano de Contas)')
                                            ->relationship('category', 'descricao', 'parent_id')
                                            ->searchable()
                                            ->required(),

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
                                            ->prefix('R$')
                                            ->placeholder('0,00')
                                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state)))
                                            ->rule('gte:0'),

                                        TextInput::make('valor_pago')
                                            ->label('Valor Pago')
                                            ->prefix('R$')
                                            ->placeholder('0,00')
                                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state)))
                                            ->rule('gte:0'),
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

    private static function parseMoney(?string $state): ?float
    {
        if (blank($state)) return null;

        return (float) str_replace(['.', ','], ['', '.'], $state);
    }

    private static function formatMoney(mixed $state): ?string
    {
        if (blank($state)) return null;

        return number_format((float) $state, 2, ',', '.');
    }
}
