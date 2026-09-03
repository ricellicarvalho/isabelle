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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Forms\Components\Placeholder;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Carbon\CarbonImmutable;

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
                                            ->live()
                                            ->native(false),

                                        Select::make('status')
                                            ->label('Status')
                                            ->options(fn ($record): array => in_array($record?->status, ['finalizado', 'cancelado'])
                                                ? [
                                                    'rascunho' => 'Rascunho',
                                                    'ativo' => 'Ativo',
                                                    'finalizado' => 'Finalizado',
                                                    'cancelado' => 'Cancelado',
                                                ]
                                                : [
                                                    'rascunho' => 'Rascunho',
                                                    'ativo' => 'Ativo',
                                                ]
                                            )
                                            ->disabled(fn ($record): bool => in_array($record?->status, ['finalizado', 'cancelado']))
                                            ->dehydrated()
                                            ->helperText(fn ($record): string|HtmlString|null => match ($record?->status) {
                                                'finalizado' => new HtmlString('<span style="color:#16a34a;font-weight:600;">Este contrato foi finalizado automaticamente pelo sistema ao atingir a data de encerramento.</span>'),
                                                'cancelado' => new HtmlString('<span style="color:#dc2626;font-weight:600;">Este contrato foi cancelado.</span>'),
                                                default => null,
                                            })
                                            ->default('rascunho')
                                            ->required()
                                            ->native(false),

                                        Textarea::make('descricao')
                                            ->label('Descrição')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),

                                Section::make()
                                    ->visible(fn (Get $get): bool => $get('tipo_servico') === 'nr1')
                                    ->compact()
                                    ->components([
                                        Placeholder::make('nr1_orientation')
                                            ->hiddenLabel()
                                            ->content(fn ($record): HtmlString => self::nr1Orientation($record !== null)),
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
                                    ->description(fn ($record): ?string => $record && $record->status !== 'rascunho'
                                        ? 'Para ajustar uma vigência cadastrada incorretamente, use a ação “Corrigir contrato” no topo da página.'
                                        : null)
                                    ->columns(2)
                                    ->components([
                                        DatePicker::make('data_inicio')
                                            ->label('Data de Início')
                                            ->required()
                                            ->disabled(fn ($record): bool => $record && $record->status !== 'rascunho')
                                            ->live()
                                            ->afterStateUpdated(function ($state, Set $set, $record): void {
                                                if ($record || blank($state)) {
                                                    return;
                                                }

                                                $set('data_fim', CarbonImmutable::parse($state)->addYearNoOverflow()->toDateString());
                                            })
                                            ->native(false)
                                            ->displayFormat('d/m/Y'),

                                        DatePicker::make('data_fim')
                                            ->label('Data de Fim')
                                            ->required()
                                            ->disabled(fn ($record): bool => $record && $record->status !== 'rascunho')
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
                                            ->preserveFilenames()
                                            ->maxSize(10240),
                                    ]),

                                Section::make('Observações')
                                    ->components([
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->disabled(fn ($record): bool => $record && $record->status !== 'rascunho')
                                            ->helperText(fn ($record): ?string => $record && $record->status !== 'rascunho'
                                                ? 'Use “Corrigir contrato” para alterar esta informação com registro no histórico.'
                                                : null)
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

    private static function nr1Orientation(bool $isEditing): HtmlString
    {
        $message = $isEditing
            ? 'Use <strong>Preencher checklist NR-1/ANO</strong>, no topo da página, para atualizar o ciclo vigente. Consulte todos os anos na aba <strong>Checklist NR-1 por ano</strong> abaixo do formulário.'
            : 'Depois de salvar, o sistema criará a NR-1 do ano inicial da vigência. Você será direcionado para usar <strong>Preencher checklist NR-1/ANO</strong> e consultar o histórico em <strong>Checklist NR-1 por ano</strong>.';

        return new HtmlString(
            '<div style="display:flex;gap:12px;align-items:flex-start;background:linear-gradient(135deg,#f5f3ff,#ede9fe);border:1.5px solid #8b5cf6;border-left:5px solid #7c3aed;border-radius:12px;padding:14px 16px;color:#2e1065;box-shadow:0 3px 10px rgba(124,58,237,.12)">'
            .'<div style="display:flex;align-items:center;justify-content:center;flex:0 0 34px;height:34px;border-radius:9px;background:#7c3aed;color:white;font-size:18px;font-weight:900">✓</div>'
            .'<div><div style="font-size:.95rem;font-weight:800;margin-bottom:4px">Onde preencher a NR-1?</div>'
            .'<div style="font-size:.84rem;line-height:1.6;color:#4c1d95">'.$message.'</div></div></div>'
        );
    }

}
