<?php

namespace App\Filament\Resources\Pricings\Schemas;

use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class PricingForm
{
    // Máscara de centavos, igual ao padrão do sistema (x-on:input, digits-only, LTR)
    private const MONEY_MASK = "let v=\$event.target.value.replace(/\\D/g,'');if(!v)v='0';v=v.replace(/^0+/,'')||'0';while(v.length<3)v='0'+v;let d=v.slice(-2),i=v.slice(0,-2).replace(/^0+/,'')||'0';i=i.replace(/\\B(?=(\\d{3})+(?!\\d))/g,'.');\$event.target.value=i+','+d;";

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make('tabs')
                    ->columnSpanFull()
                    ->tabs([

                        // ── Tab 1: Identificação ───────────────────────────
                        Tab::make('Identificação')
                            ->icon(Heroicon::Tag)
                            ->components([
                                Section::make()
                                    ->columns(2)
                                    ->components([
                                        TextInput::make('nome')
                                            ->label('Nome do Serviço / Ação')
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
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        // ── Tab 2: Precificação ────────────────────────────
                        Tab::make('Precificação')
                            ->icon(Heroicon::Calculator)
                            ->components([

                                // Parâmetros
                                Section::make('Parâmetros')
                                    ->icon(Heroicon::AdjustmentsHorizontal)
                                    ->columns(3)
                                    ->components([
                                        TextInput::make('margem_lucro')
                                            ->label('Margem de Lucro (%)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(30)
                                            ->minValue(0)
                                            ->maxValue(99.99)
                                            ->live(onBlur: true),

                                        TextInput::make('percentual_imposto')
                                            ->label('Imposto / NFSe (%)')
                                            ->numeric()
                                            ->suffix('%')
                                            ->default(8)
                                            ->minValue(0)
                                            ->live(onBlur: true),

                                        TextInput::make('quantidade_parcelas')
                                            ->label('Parcelas')
                                            ->numeric()
                                            ->integer()
                                            ->default(1)
                                            ->minValue(1)
                                            ->live(onBlur: true),
                                    ]),

                                // Calculadora NR-1 — 12 cols: label(3) | entrada(5) | lucro(2) | total(2)
                                Section::make('Calculadora NR-1')
                                    ->icon(Heroicon::TableCells)
                                    ->columns(12)
                                    ->components([

                                        // Cabeçalho
                                        self::thCell('Serviço', 3),
                                        self::thCell('Custo Médio', 5),
                                        self::thCell('Lucro', 2),
                                        self::thCell('Total s/ Imposto', 2),

                                        // Encontro
                                        self::tdLabel('Encontro', 3),
                                        self::moneyField('despesa_encontro', '1.000,00', 5),
                                        self::calcCell('lucro_enc', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_encontro')) * self::pct($get), 2, 'lucro'),
                                        self::calcCell('total_enc', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_encontro')) * (1 + self::pct($get)), 2, 'total'),

                                        // Aplicação
                                        self::tdLabel('Aplicação', 3),
                                        TextInput::make('num_funcionarios')
                                            ->hiddenLabel()
                                            ->placeholder('Qtd funcionários')
                                            ->numeric()
                                            ->integer()
                                            ->minValue(0)
                                            ->live(onBlur: true)
                                            ->columnSpan(2),
                                        self::moneyField('valor_por_funcionario', '100,00', 3),
                                        self::calcCell('lucro_apl', static fn (Get $get): float =>
                                            self::custoApl($get) * self::pct($get), 2, 'lucro'),
                                        self::calcCell('total_apl', static fn (Get $get): float =>
                                            self::custoApl($get) * (1 + self::pct($get)), 2, 'total'),

                                        // Risco
                                        self::tdLabel('Risco', 3),
                                        self::moneyField('despesa_risco', '200,00', 5),
                                        self::calcCell('lucro_ris', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_risco')) * self::pct($get), 2, 'lucro'),
                                        self::calcCell('total_ris', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_risco')) * (1 + self::pct($get)), 2, 'total'),

                                        // Relatório
                                        self::tdLabel('Relatório', 3),
                                        self::moneyField('despesa_relatorio', '200,00', 5),
                                        self::calcCell('lucro_rel', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_relatorio')) * self::pct($get), 2, 'lucro'),
                                        self::calcCell('total_rel', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_relatorio')) * (1 + self::pct($get)), 2, 'total'),

                                        // Despesas Indiretas (sem lucro)
                                        self::tdLabel('Despesas', 3),
                                        self::moneyField('despesas_indiretas', '362,00', 5),
                                        Placeholder::make('lucro_desp')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<span style="color:#9ca3af;">—</span>'))
                                            ->columnSpan(2),
                                        self::calcCell('total_desp', static fn (Get $get): float =>
                                            self::parseMoney($get('despesas_indiretas')), 2, 'total'),

                                        // Ação Anual
                                        self::tdLabel('Ação Anual', 3),
                                        self::moneyField('despesa_acao_anual', '200,00', 5),
                                        self::calcCell('lucro_aca', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_acao_anual')) * self::pct($get), 2, 'lucro'),
                                        self::calcCell('total_aca', static fn (Get $get): float =>
                                            self::parseMoney($get('despesa_acao_anual')) * (1 + self::pct($get)), 2, 'total'),

                                        // Deslocamento (sem margem de lucro)
                                        self::tdLabel('Deslocamento', 3),
                                        self::moneyField('deslocamento', '0,00', 5),
                                        Placeholder::make('lucro_des')
                                            ->hiddenLabel()
                                            ->content(new HtmlString('<span style="color:#9ca3af;">—</span>'))
                                            ->columnSpan(2),
                                        self::calcCell('total_des', static fn (Get $get): float =>
                                            self::parseMoney($get('deslocamento')), 2, 'total'),

                                        // Linha de totais
                                        Placeholder::make('tot_lbl')
                                            ->hiddenLabel()
                                            ->content(new HtmlString(
                                                '<div style="font-weight:700;font-size:13px;color:#1e1b4b;'
                                                . 'border-top:2px solid #4338ca;padding-top:6px;">TOTAL</div>'
                                            ))
                                            ->columnSpan(3),
                                        Placeholder::make('tot_custo')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => new HtmlString(
                                                '<div style="font-weight:700;color:#374151;border-top:2px solid #4338ca;padding-top:6px;">'
                                                . self::fmt(self::custoTotal($get)) . '</div>'
                                            ))
                                            ->columnSpan(5),
                                        Placeholder::make('tot_lucro')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => new HtmlString(
                                                '<div style="font-weight:700;color:#15803d;border-top:2px solid #4338ca;padding-top:6px;">'
                                                . self::fmt(self::sImposto($get) - self::custoTotal($get)) . '</div>'
                                            ))
                                            ->columnSpan(2),
                                        Placeholder::make('tot_simp')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => new HtmlString(
                                                '<div style="font-weight:700;color:#4338ca;border-top:2px solid #4338ca;padding-top:6px;">'
                                                . self::fmt(self::sImposto($get)) . '</div>'
                                            ))
                                            ->columnSpan(2),
                                    ]),

                                // Resumo Financeiro
                                Section::make('Resumo Financeiro')
                                    ->icon(Heroicon::Banknotes)
                                    ->columns(3)
                                    ->components([
                                        Placeholder::make('r_custo')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => self::card(
                                                'Total Custo Médio',
                                                self::custoTotal($get),
                                                '#f3f4f6', '#374151', '#6b7280'
                                            )),

                                        Placeholder::make('r_simp')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => self::card(
                                                'Total s/ Imposto',
                                                self::sImposto($get),
                                                '#eff6ff', '#1d4ed8', '#3b82f6'
                                            )),

                                        Placeholder::make('r_cimp')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => self::card(
                                                'Total c/ Imposto',
                                                self::cImposto($get),
                                                '#eef2ff', '#4338ca', '#6366f1',
                                                true
                                            )),

                                        Placeholder::make('r_parcela')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => new HtmlString(
                                                '<div style="background:#fffbeb;border-radius:8px;padding:12px 16px;text-align:center;">'
                                                . '<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:#d97706;margin-bottom:4px;">'
                                                . 'Valor por Parcela (' . max(1, (int) ($get('quantidade_parcelas') ?? 1)) . 'x)</div>'
                                                . '<div style="font-size:22px;font-weight:700;color:#92400e;">'
                                                . self::fmt(self::cImposto($get) / max(1, (int) ($get('quantidade_parcelas') ?? 1)))
                                                . '</div></div>'
                                            )),

                                        Placeholder::make('r_lucro')
                                            ->hiddenLabel()
                                            ->content(static fn (Get $get): HtmlString => self::card(
                                                'Lucro Final',
                                                self::cImposto($get) - self::custoTotal($get),
                                                '#f0fdf4', '#15803d', '#16a34a'
                                            )),
                                    ]),
                            ]),

                        // ── Tab 3: Observações ─────────────────────────────
                        Tab::make('Observações')
                            ->icon(Heroicon::ChatBubbleBottomCenterText)
                            ->components([
                                Section::make()
                                    ->components([
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->rows(5)
                                            ->columnSpanFull(),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    // ── Helpers de células ─────────────────────────────────────────────────

    private static function thCell(string $label, int $span): Placeholder
    {
        return Placeholder::make('th_' . preg_replace('/[^a-z0-9]/i', '_', $label))
            ->hiddenLabel()
            ->content(new HtmlString(
                '<div style="background:#4338ca;color:#fff;font-weight:700;font-size:11px;'
                . 'text-transform:uppercase;letter-spacing:.06em;padding:6px 8px;border-radius:6px;">'
                . e($label) . '</div>'
            ))
            ->columnSpan($span);
    }

    private static function tdLabel(string $label, int $span): Placeholder
    {
        return Placeholder::make('td_' . preg_replace('/[^a-z0-9]/i', '_', $label))
            ->hiddenLabel()
            ->content(new HtmlString(
                '<span style="font-weight:600;color:#1e40af;font-size:13px;">' . e($label) . '</span>'
            ))
            ->columnSpan($span);
    }

    private static function moneyField(string $field, string $default, int $span): TextInput
    {
        return TextInput::make($field)
            ->hiddenLabel()
            ->prefix('R$')
            ->placeholder('0,00')
            ->extraAlpineAttributes(['x-on:input' => self::MONEY_MASK])
            ->afterStateHydrated(fn (TextInput $c, $state) => $c->state(
                blank($state) ? $default : self::formatMoney($state)
            ))
            ->live(onBlur: true)
            ->columnSpan($span);
    }

    private static function calcCell(string $name, \Closure $fn, int $span, string $type): Placeholder
    {
        $color = $type === 'lucro' ? '#15803d' : '#4338ca';

        return Placeholder::make($name)
            ->hiddenLabel()
            ->content(static function (Get $get) use ($fn, $color): HtmlString {
                return new HtmlString(
                    '<span style="color:' . $color . ';font-weight:600;">' . self::fmt($fn($get)) . '</span>'
                );
            })
            ->columnSpan($span);
    }

    private static function card(
        string $title,
        float $value,
        string $bg,
        string $valuColor,
        string $titleColor,
        bool $large = false,
    ): HtmlString {
        $size   = $large ? '28px' : '22px';
        $border = $large ? 'border:2px solid #6366f1;' : '';

        return new HtmlString(
            '<div style="background:' . $bg . ';border-radius:8px;padding:12px 16px;text-align:center;' . $border . '">'
            . '<div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em;color:'
            . $titleColor . ';margin-bottom:4px;">' . e($title) . '</div>'
            . '<div style="font-size:' . $size . ';font-weight:' . ($large ? '800' : '700') . ';color:'
            . $valuColor . ';">' . self::fmt($value) . '</div>'
            . '</div>'
        );
    }

    // ── Cálculos ───────────────────────────────────────────────────────────

    private static function pct(Get $get): float
    {
        $v = $get('margem_lucro');
        return (float) ($v ?? 30) / 100;
    }

    private static function custoApl(Get $get): float
    {
        return (int) ($get('num_funcionarios') ?? 0) * self::parseMoney($get('valor_por_funcionario'));
    }

    public static function custoTotal(Get $get): float
    {
        return self::parseMoney($get('despesa_encontro'))
            + self::custoApl($get)
            + self::parseMoney($get('despesa_risco'))
            + self::parseMoney($get('despesa_relatorio'))
            + self::parseMoney($get('despesas_indiretas'))
            + self::parseMoney($get('despesa_acao_anual'))
            + self::parseMoney($get('deslocamento'));
    }

    public static function sImposto(Get $get): float
    {
        $f = 1 + self::pct($get);

        return self::parseMoney($get('despesa_encontro')) * $f
            + self::custoApl($get) * $f
            + self::parseMoney($get('despesa_risco')) * $f
            + self::parseMoney($get('despesa_relatorio')) * $f
            + self::parseMoney($get('despesas_indiretas'))   // sem lucro
            + self::parseMoney($get('despesa_acao_anual')) * $f
            + self::parseMoney($get('deslocamento'));         // sem lucro
    }

    public static function cImposto(Get $get): float
    {
        $imp = (float) ($get('percentual_imposto') ?? 8) / 100;

        return self::sImposto($get) * (1 + $imp);
    }

    // ── Formatação ─────────────────────────────────────────────────────────

    public static function parseMoney(mixed $state): float
    {
        if (blank($state)) {
            return 0.0;
        }
        if (is_numeric($state)) {
            return (float) $state;
        }

        $str = (string) $state;

        // wire:model captura o valor ANTES da máscara JS reformatar, gerando strings como
        // "10,000" (estado intermediário de "100,00") ou "1.000,000" (intermediário de "10.000,00").
        // Nesses casos a última vírgula tem mais de 2 dígitos à direita — tratar tudo como centavos.
        $lastComma = strrpos($str, ',');
        if ($lastComma !== false && strlen(substr($str, $lastComma + 1)) > 2) {
            $digits = preg_replace('/\D/', '', $str);
            return $digits !== '' ? (float) $digits / 100 : 0.0;
        }

        return (float) str_replace(['.', ','], ['', '.'], $str);
    }

    private static function formatMoney(mixed $state): string
    {
        return number_format(self::parseMoney($state), 2, ',', '.');
    }

    private static function fmt(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
