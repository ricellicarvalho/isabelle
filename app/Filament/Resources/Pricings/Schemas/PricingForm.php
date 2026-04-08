<?php

namespace App\Filament\Resources\Pricings\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class PricingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identificação')
                    ->icon(Heroicon::Tag)
                    ->columns(2)
                    ->components([
                        TextInput::make('nome')
                            ->label('Nome do Serviço/Ação')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        SelectTree::make('category_id')
                            ->label('Categoria (Plano de Contas)')
                            ->relationship('category', 'descricao', 'parent_id')
                            ->searchable()
                            ->required()
                            ->columnSpanFull(),

                        Textarea::make('descricao')
                            ->label('Descrição')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Calculadora de Margem')
                    ->icon(Heroicon::Calculator)
                    ->description('O preço de venda é calculado automaticamente: Preço = Custo Total ÷ (1 − Margem%)')
                    ->columns(2)
                    ->components([
                        TextInput::make('custo_direto')
                            ->label('Custo Direto (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),

                        TextInput::make('custo_indireto')
                            ->label('Custo Indireto (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->default(0)
                            ->minValue(0)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),

                        TextInput::make('margem_lucro')
                            ->label('Margem de Lucro (%)')
                            ->numeric()
                            ->suffix('%')
                            ->default(0)
                            ->minValue(0)
                            ->maxValue(99.99)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),

                        TextInput::make('preco_venda')
                            ->label('Preço de Venda (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->readOnly()
                            ->helperText('Calculado automaticamente'),

                        Placeholder::make('custo_total_label')
                            ->label('Custo Total')
                            ->content(function (Get $get): string {
                                $total = (float) ($get('custo_direto') ?? 0) + (float) ($get('custo_indireto') ?? 0);

                                return 'R$ ' . number_format($total, 2, ',', '.');
                            }),

                        Placeholder::make('margem_real_label')
                            ->label('Lucro por Venda')
                            ->content(function (Get $get): string {
                                $custo = (float) ($get('custo_direto') ?? 0) + (float) ($get('custo_indireto') ?? 0);
                                $preco = (float) ($get('preco_venda') ?? 0);

                                return 'R$ ' . number_format($preco - $custo, 2, ',', '.');
                            }),
                    ]),

                Section::make('Observações')
                    ->collapsed()
                    ->components([
                        Textarea::make('observacoes')
                            ->label('Observações')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function recalcular(Get $get, Set $set): void
    {
        $custoDireto   = (float) ($get('custo_direto') ?? 0);
        $custoIndireto = (float) ($get('custo_indireto') ?? 0);
        $margem        = (float) ($get('margem_lucro') ?? 0);

        $custoTotal = $custoDireto + $custoIndireto;

        if ($margem >= 100) {
            $set('preco_venda', 0);

            return;
        }

        // Fórmula: preço = custo / (1 - margem/100)
        $preco = $margem > 0
            ? round($custoTotal / (1 - $margem / 100), 2)
            : $custoTotal;

        $set('preco_venda', $preco);
    }
}
