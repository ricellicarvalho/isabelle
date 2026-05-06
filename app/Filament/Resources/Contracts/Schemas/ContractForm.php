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
                                            ->placeholder('0,00')
                                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state))),

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

    public static function parseMoney(mixed $state): ?float
    {
        if (blank($state)) return null;
        if (is_numeric($state)) return (float) $state;

        $str = (string) $state;

        // x-model captura o valor antes da máscara JS reformatar, gerando strings como
        // "0,100" (intermediário de "1,00") ou "0,199" (intermediário de "1,99").
        // Quando há mais de 2 dígitos após a última vírgula, trata tudo como centavos.
        $lastComma = strrpos($str, ',');
        if ($lastComma !== false && strlen(substr($str, $lastComma + 1)) > 2) {
            $digits = preg_replace('/\D/', '', $str);
            return $digits !== '' ? (float) $digits / 100 : 0.0;
        }

        return (float) str_replace(['.', ','], ['', '.'], $str);
    }

    private static function formatMoney(mixed $state): ?string
    {
        if (blank($state)) return null;

        return number_format((float) $state, 2, ',', '.');
    }
}
