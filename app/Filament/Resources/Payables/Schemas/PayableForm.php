<?php

namespace App\Filament\Resources\Payables\Schemas;

use App\Models\Payable;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;

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
                                            ->live(debounce: 500)
                                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state)))
                                            ->rule(fn () => function (string $attribute, $state, \Closure $fail) {
                                                if ((self::parseMoney($state) ?? 0) < 0) {
                                                    $fail('O campo Valor não pode ser negativo.');
                                                }
                                            }),

                                        TextInput::make('valor_pago')
                                            ->label('Valor Pago')
                                            ->prefix('R$')
                                            ->placeholder('0,00')
                                            ->visible(fn (Get $get): bool => ! (bool) $get('recorrente'))
                                            ->extraAlpineAttributes(['x-on:input' => "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;"])
                                            ->afterStateHydrated(fn (TextInput $component, $state) => $component->state(self::formatMoney($state)))
                                            ->rule(fn () => function (string $attribute, $state, \Closure $fail) {
                                                if ((self::parseMoney($state) ?? 0) < 0) {
                                                    $fail('O campo Valor Pago não pode ser negativo.');
                                                }
                                            }),
                                    ]),
                            ]),

                        Tab::make('Pagamento')
                            ->icon(Heroicon::CalendarDays)
                            ->components([
                                Placeholder::make('recurrence_info')
                                    ->hiddenLabel()
                                    ->content(fn (?Payable $record): HtmlString => new HtmlString(
                                        '<div class="rounded-lg bg-info-50 p-4 font-medium text-info-800 dark:bg-info-950/40 dark:text-info-200">'
                                        ."Esta conta pertence à recorrência {$record?->recurrence_sequence} de {$record?->recurrence_total}. As alterações afetam somente esta conta."
                                        .'</div>'
                                    ))
                                    ->visible(fn (?Payable $record, string $operation): bool => $operation === 'edit' && filled($record?->payable_recurrence_id)),

                                Section::make('Vencimento e Pagamento')
                                    ->columns(2)
                                    ->components([
                                        DatePicker::make('data_vencimento')
                                            ->label('Data de Vencimento')
                                            ->required()
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->live(),

                                        DatePicker::make('data_pagamento')
                                            ->label('Data de Pagamento')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->visible(fn (Get $get): bool => ! (bool) $get('recorrente')),

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
                                            ->native(false)
                                            ->visible(fn (Get $get): bool => ! (bool) $get('recorrente')),
                                    ]),

                                Section::make('Recorrência')
                                    ->description('Gere automaticamente uma conta para cada mês do período.')
                                    ->columns(2)
                                    ->visible(fn (string $operation): bool => $operation === 'create')
                                    ->components([
                                        Toggle::make('recorrente')
                                            ->label('Gerar contas recorrentes')
                                            ->default(false)
                                            ->live()
                                            ->columnSpanFull(),

                                        Select::make('frequencia_recorrencia')
                                            ->label('Frequência')
                                            ->options(['monthly' => 'Mensal'])
                                            ->default('monthly')
                                            ->required(fn (Get $get): bool => (bool) $get('recorrente'))
                                            ->visible(fn (Get $get): bool => (bool) $get('recorrente'))
                                            ->native(false),

                                        DatePicker::make('data_fim_recorrencia')
                                            ->label('Último vencimento')
                                            ->required(fn (Get $get): bool => (bool) $get('recorrente'))
                                            ->afterOrEqual('data_vencimento')
                                            ->native(false)
                                            ->displayFormat('d/m/Y')
                                            ->live()
                                            ->visible(fn (Get $get): bool => (bool) $get('recorrente')),

                                        Placeholder::make('resumo_recorrencia')
                                            ->label('Resumo da recorrência')
                                            ->content(fn (Get $get): HtmlString => self::recurrenceSummary($get))
                                            ->visible(fn (Get $get): bool => (bool) $get('recorrente'))
                                            ->columnSpanFull(),
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

    public static function parseMoney(mixed $state): ?float
    {
        if (blank($state)) {
            return null;
        }
        if (is_numeric($state)) {
            return (float) $state;
        }

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
        if (blank($state)) {
            return null;
        }

        return number_format((float) $state, 2, ',', '.');
    }

    private static function recurrenceSummary(Get $get): HtmlString
    {
        if (blank($get('data_vencimento')) || blank($get('data_fim_recorrencia'))) {
            return new HtmlString('<span class="text-sm text-gray-500">Informe o primeiro e o último vencimento.</span>');
        }

        $start = Carbon::parse($get('data_vencimento'));
        $end = Carbon::parse($get('data_fim_recorrencia'));
        $count = 0;

        while ($count < 120 && $start->copy()->addMonthsNoOverflow($count)->lte($end)) {
            $count++;
        }

        $value = self::parseMoney($get('valor')) ?? 0;
        $total = $value * $count;

        return new HtmlString(sprintf(
            '<div class="rounded-lg bg-primary-50 p-4 text-primary-800 dark:bg-primary-950/40 dark:text-primary-200"><strong>%d conta(s)</strong> serão criadas. Valor mensal: <strong>R$ %s</strong> — Total da série: <strong>R$ %s</strong>.</div>',
            $count,
            number_format($value, 2, ',', '.'),
            number_format($total, 2, ',', '.'),
        ));
    }
}
