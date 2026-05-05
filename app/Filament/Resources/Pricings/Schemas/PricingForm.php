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
                            ->prefix('R$')
                            ->placeholder('0,00')
                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state) ?? '0,00'))
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcular($get, $set)),

                        TextInput::make('custo_indireto')
                            ->label('Custo Indireto (R$)')
                            ->prefix('R$')
                            ->placeholder('0,00')
                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state) ?? '0,00'))
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
                            ->prefix('R$')
                            ->readOnly()
                            ->dehydrateStateUsing(fn ($state) => self::parseMoney($state))
                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state) ?? '0,00'))
                            ->helperText('Calculado automaticamente'),

                        Placeholder::make('custo_total_label')
                            ->label('Custo Total')
                            ->content(function (Get $get): string {
                                $total = self::parseMoney($get('custo_direto')) + self::parseMoney($get('custo_indireto'));

                                return 'R$ ' . number_format($total, 2, ',', '.');
                            }),

                        Placeholder::make('margem_real_label')
                            ->label('Lucro por Venda')
                            ->content(function (Get $get): string {
                                $custo = self::parseMoney($get('custo_direto')) + self::parseMoney($get('custo_indireto'));
                                $preco = self::parseMoney($get('preco_venda'));

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

    private static function parseMoney(mixed $state): float
    {
        if (blank($state)) return 0.0;
        if (is_numeric($state)) return (float) $state;

        return (float) str_replace(['.', ','], ['', '.'], (string) $state);
    }

    private static function formatMoney(mixed $state): ?string
    {
        if (blank($state)) return null;

        return number_format((float) $state, 2, ',', '.');
    }

    private static function recalcular(Get $get, Set $set): void
    {
        $custoDireto   = self::parseMoney($get('custo_direto'));
        $custoIndireto = self::parseMoney($get('custo_indireto'));
        $margem        = (float) ($get('margem_lucro') ?? 0);

        $custoTotal = $custoDireto + $custoIndireto;

        if ($margem >= 100) {
            $set('preco_venda', '0,00');

            return;
        }

        $preco = $margem > 0
            ? round($custoTotal / (1 - $margem / 100), 2)
            : $custoTotal;

        $set('preco_venda', number_format($preco, 2, ',', '.'));
    }
}
