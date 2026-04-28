<?php

namespace App\Filament\Resources\Clients\Schemas;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Support\RawJs;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

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
                                            ->mask('99999-999')
                                            ->maxLength(9)
                                            ->placeholder('00000-000')
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (?string $state, Set $set): void {
                                                self::buscarCep($state, $set);
                                            })
                                            ->suffixAction(
                                                \Filament\Actions\Action::make('buscarCep')
                                                    ->icon(Heroicon::MagnifyingGlass)
                                                    ->tooltip('Buscar CEP')
                                                    ->action(function (Get $get, Set $set): void {
                                                        self::buscarCep($get('cep'), $set);
                                                    })
                                            ),

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

                                        Select::make('uf')
                                            ->label('UF')
                                            ->options(self::ufs())
                                            ->searchable()
                                            ->native(false)
                                            ->live()
                                            ->afterStateUpdated(fn (Set $set) => $set('cidade', null)),

                                        Select::make('cidade')
                                            ->label('Cidade')
                                            ->options(function (Get $get, ?string $state): array {
                                                $uf = $get('uf');
                                                $cidades = $uf ? self::cidadesPorUf($uf) : [];

                                                // Inclui o valor atual para não perder dados legados
                                                if (filled($state) && ! isset($cidades[$state])) {
                                                    $cidades[$state] = $state;
                                                }

                                                return $cidades;
                                            })
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
                                        TextInput::make('contato_nome')
                                            ->label('Nome do Contato')
                                            ->maxLength(255),

                                        TextInput::make('email')
                                            ->label('E-mail')
                                            ->email()
                                            ->rule('email:rfc,dns')
                                            ->maxLength(255)
                                            ->validationMessages([
                                                'email' => 'Informe um endereço de e-mail válido.',
                                                'email:rfc,dns' => 'E-mail inválido ou domínio não encontrado.',
                                            ]),
                                    ]),

                                Section::make('Telefones')
                                    ->icon(Heroicon::Phone)
                                    ->iconColor('primary')
                                    ->components([
                                        Repeater::make('telefones')
                                            ->label('')
                                            ->schema([
                                                Select::make('tipo')
                                                    ->label('Tipo')
                                                    ->options([
                                                        'celular'  => '📱 Celular',
                                                        'fixo'     => '📞 Fixo',
                                                        'whatsapp' => '💬 WhatsApp',
                                                        'trabalho' => '🏢 Trabalho',
                                                        'recado'   => '📝 Recado',
                                                    ])
                                                    ->required()
                                                    ->native(false)
                                                    ->default('celular'),

                                                TextInput::make('numero')
                                                    ->label('Número')
                                                    ->required()
                                                    ->mask(RawJs::make(<<<'JS'
                                                        $input.replace(/\D/g, '').length > 10
                                                            ? '(99) 99999-9999'
                                                            : '(99) 9999-9999'
                                                    JS))
                                                    ->placeholder('(00) 00000-0000')
                                                    ->maxLength(15)
                                                    ->dehydrateStateUsing(fn (?string $state): ?string => $state ? preg_replace('/\D/', '', $state) : null)
                                                    ->formatStateUsing(function (?string $state): ?string {
                                                        if (! $state) {
                                                            return null;
                                                        }
                                                        $digits = preg_replace('/\D/', '', $state);

                                                        return match (strlen($digits)) {
                                                            11 => preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits),
                                                            10 => preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits),
                                                            default => $state,
                                                        };
                                                    }),
                                            ])
                                            ->columns(2)
                                            ->defaultItems(1)
                                            ->addActionLabel('Adicionar telefone')
                                            ->reorderable()
                                            ->extraAttributes([
                                                'class' => 'bg-gray-50 dark:bg-white/5 rounded-xl p-4 ring-1 ring-gray-950/5 dark:ring-white/10',
                                            ]),
                                    ]),
                            ]),

                        Tab::make('NR-1')
                            ->icon(Heroicon::ClipboardDocumentCheck)
                            ->components([
                                // --- Status + Progresso (topo) ---
                                Section::make('Status de Conformidade')
                                    ->icon(Heroicon::ShieldCheck)
                                    ->iconColor('primary')
                                    ->compact()
                                    ->columns(2)
                                    ->components([
                                        Select::make('nr1_status')
                                            ->label('Status NR-1')
                                            ->options([
                                                'pendente'     => 'Pendente',
                                                'em_andamento' => 'Em Andamento',
                                                'regularizada' => 'Regularizada',
                                                'finalizada'   => 'Finalizada',
                                            ])
                                            ->default('pendente')
                                            ->disabled()
                                            ->dehydrated()
                                            ->native(false)
                                            ->helperText('Calculado automaticamente conforme as etapas.'),

                                        Placeholder::make('nr1_progresso')
                                            ->label('Progresso')
                                            ->content(function (Get $get): HtmlString {
                                                $etapas = ['etapa1', 'etapa2', 'etapa3', 'etapa4', 'etapa5'];
                                                $checklist = $get('nr1_checklist') ?? [];
                                                $done = count(array_filter($etapas, fn ($e) => ! empty($checklist[$e])));
                                                $pct = (int) round(($done / count($etapas)) * 100);

                                                $color = match (true) {
                                                    $pct === 100 => '#22c55e',
                                                    $pct >= 60   => '#8b5cf6',
                                                    $pct > 0     => '#f59e0b',
                                                    default      => '#e5e7eb',
                                                };

                                                return new HtmlString(
                                                    '<div style="margin-top: 6px;">'
                                                    . '<div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">'
                                                    . '<span style="font-size:1.25rem; font-weight:700; color:' . $color . ';">' . $pct . '%</span>'
                                                    . '<span style="color:#6b7280; font-size:0.85rem;">' . $done . ' de 5 etapas</span>'
                                                    . '</div>'
                                                    . '<div style="width:100%; height:8px; background:#e5e7eb; border-radius:9999px; overflow:hidden;">'
                                                    . '<div style="width:' . $pct . '%; height:100%; background:' . $color . '; border-radius:9999px; transition:width 0.4s ease;"></div>'
                                                    . '</div>'
                                                    . '</div>'
                                                );
                                            }),
                                    ]),

                                // --- Checklist de Etapas ---
                                Section::make('Checklist de Conformidade NR-1')
                                    ->description('Marque cada etapa conforme concluída. Etapas 1-3 = Em Andamento | 1-4 = Regularizada | 1-5 = Finalizada.')
                                    ->icon(Heroicon::ListBullet)
                                    ->iconColor('primary')
                                    ->columns(1)
                                    ->components([
                                        // Etapa 1
                                        Section::make('Etapa 1: Encontro')
                                            ->icon(Heroicon::UserGroup)
                                            ->iconColor(fn (Get $get): string => ! empty($get('nr1_checklist.etapa1')) ? 'success' : 'gray')
                                            ->compact()
                                            ->columns(3)
                                            ->components([
                                                Toggle::make('nr1_checklist.etapa1')
                                                    ->label(new HtmlString('<span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Concluída</span>'))
                                                    ->live()
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularStatus($get, $set)),

                                                Select::make('nr1_checklist.etapa1_tipo')
                                                    ->label('Modalidade')
                                                    ->options([
                                                        'presencial' => 'Presencial',
                                                        'online'     => 'Online',
                                                    ])
                                                    ->native(false)
                                                    ->placeholder('Selecione')
                                                    ,

                                                DatePicker::make('nr1_checklist.etapa1_data')
                                                    ->label('Data')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ,
                                            ]),

                                        // Etapa 2
                                        Section::make('Etapa 2: Avaliação dos Riscos Psicossociais')
                                            ->icon(Heroicon::MagnifyingGlass)
                                            ->iconColor(fn (Get $get): string => ! empty($get('nr1_checklist.etapa2')) ? 'success' : 'gray')
                                            ->compact()
                                            ->columns(2)
                                            ->components([
                                                Toggle::make('nr1_checklist.etapa2')
                                                    ->label(new HtmlString('<span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Concluída</span>'))
                                                    ->live()
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularStatus($get, $set)),

                                                DatePicker::make('nr1_checklist.etapa2_data')
                                                    ->label('Data')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ,
                                            ]),

                                        // Etapa 3
                                        Section::make('Etapa 3: Relatório Diagnóstico (DPRS)')
                                            ->icon(Heroicon::DocumentChartBar)
                                            ->iconColor(fn (Get $get): string => ! empty($get('nr1_checklist.etapa3')) ? 'success' : 'gray')
                                            ->compact()
                                            ->columns(2)
                                            ->components([
                                                Toggle::make('nr1_checklist.etapa3')
                                                    ->label(new HtmlString('<span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Concluída</span>'))
                                                    ->live()
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularStatus($get, $set)),

                                                DatePicker::make('nr1_checklist.etapa3_data')
                                                    ->label('Data')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ,
                                            ]),

                                        // Etapa 4
                                        Section::make('Etapa 4: Matriz de Risco (Segurança do Trabalho)')
                                            ->icon(Heroicon::ShieldExclamation)
                                            ->iconColor(fn (Get $get): string => ! empty($get('nr1_checklist.etapa4')) ? 'success' : 'gray')
                                            ->compact()
                                            ->columns(2)
                                            ->components([
                                                Toggle::make('nr1_checklist.etapa4')
                                                    ->label(new HtmlString('<span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Concluída</span>'))
                                                    ->live()
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularStatus($get, $set)),

                                                DatePicker::make('nr1_checklist.etapa4_data')
                                                    ->label('Data')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ,
                                            ]),

                                        // Etapa 5
                                        Section::make('Etapa 5: Devolutiva')
                                            ->icon(Heroicon::ChatBubbleLeftRight)
                                            ->iconColor(fn (Get $get): string => ! empty($get('nr1_checklist.etapa5')) ? 'success' : 'gray')
                                            ->compact()
                                            ->columns(2)
                                            ->components([
                                                Toggle::make('nr1_checklist.etapa5')
                                                    ->label(new HtmlString('<span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Concluída</span>'))
                                                    ->live()
                                                    ->afterStateUpdated(fn (Get $get, Set $set) => self::recalcularStatus($get, $set)),

                                                DatePicker::make('nr1_checklist.etapa5_data')
                                                    ->label('Data')
                                                    ->native(false)
                                                    ->displayFormat('d/m/Y')
                                                    ,
                                            ]),
                                    ]),

                                Section::make('Observações')
                                    ->components([
                                        Textarea::make('observacoes')
                                            ->label('Observações')
                                            ->rows(4)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Tab::make('Portal do Cliente')
                            ->icon(Heroicon::GlobeAlt)
                            ->components([
                                Section::make('Acesso ao Portal')
                                    ->icon(Heroicon::LockClosed)
                                    ->iconColor('primary')
                                    ->description('Gerencie o acesso do cliente ao portal usando os botões no topo da página.')
                                    ->columns(2)
                                    ->components([
                                        Placeholder::make('portal_status_badge')
                                            ->label('Status do Acesso')
                                            ->content(function ($record): HtmlString {
                                                if (! $record?->portal_user_id) {
                                                    return new HtmlString(
                                                        '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400">'
                                                        . '<span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Sem acesso'
                                                        . '</span>'
                                                    );
                                                }

                                                return new HtmlString(
                                                    '<span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">'
                                                    . '<span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Ativo'
                                                    . '</span>'
                                                );
                                            }),

                                        Placeholder::make('portal_access_email')
                                            ->label('E-mail de Login')
                                            ->content(fn ($record): string => $record?->email ?? '—')
                                            ->visible(fn ($record): bool => (bool) $record?->portal_user_id),

                                        Placeholder::make('portal_access_created')
                                            ->label('Acesso criado em')
                                            ->content(fn ($record): string => $record?->portalUser?->created_at?->format('d/m/Y H:i') ?? '—')
                                            ->visible(fn ($record): bool => (bool) $record?->portal_user_id),

                                        Placeholder::make('portal_no_access_info')
                                            ->label('')
                                            ->content(new HtmlString(
                                                '<p class="text-sm text-gray-500 dark:text-gray-400">'
                                                . 'Nenhum acesso configurado. Use o botão <strong>"Gerar Acesso ao Portal"</strong> '
                                                . 'no topo desta página para criar um login e senha para este cliente.'
                                                . '</p>'
                                            ))
                                            ->columnSpanFull()
                                            ->visible(fn ($record): bool => ! $record?->portal_user_id),
                                    ]),
                            ]),
                    ])
                    ->activeTab(1)
                    ->contained(false)
                    ->columnSpanFull(),
            ]);
    }

    /**
     * Recalcula o status NR-1 reativamente no formulário a cada checkbox alterado.
     * Etapas 1-3 = em_andamento | Etapas 1-4 = regularizada | Etapas 1-5 = finalizada
     */
    public static function recalcularStatus(Get $get, Set $set): void
    {
        $checklist = $get('nr1_checklist') ?? [];

        $etapa1 = ! empty($checklist['etapa1']);
        $etapa2 = ! empty($checklist['etapa2']);
        $etapa3 = ! empty($checklist['etapa3']);
        $etapa4 = ! empty($checklist['etapa4']);
        $etapa5 = ! empty($checklist['etapa5']);

        $status = match (true) {
            $etapa1 && $etapa2 && $etapa3 && $etapa4 && $etapa5 => 'finalizada',
            $etapa1 && $etapa2 && $etapa3 && $etapa4            => 'regularizada',
            $etapa1 && $etapa2 && $etapa3                       => 'em_andamento',
            default                                              => 'pendente',
        };

        $set('nr1_status', $status);
    }

    /**
     * Consulta a API ViaCEP e preenche automaticamente os campos de endereço.
     */
    public static function buscarCep(?string $cep, Set $set): void
    {
        $cep = preg_replace('/\D/', '', $cep ?? '');

        if (strlen($cep) !== 8) {
            return;
        }

        try {
            $response = Http::timeout(5)->get("https://viacep.com.br/ws/{$cep}/json/");

            if ($response->successful() && ! ($response->json('erro') ?? false)) {
                $data = $response->json();
                $set('endereco', $data['logradouro'] ?? '');
                $set('complemento', $data['complemento'] ?? '');
                $set('bairro', $data['bairro'] ?? '');
                $set('uf', $data['uf'] ?? '');
                $set('cidade', $data['localidade'] ?? '');
            }
        } catch (\Throwable) {
            // Falha silenciosa — o usuário preenche manualmente
        }
    }

    /**
     * Retorna as cidades de uma UF via API IBGE.
     * Resultado em cache por 24h para não sobrecarregar a API.
     */
    public static function cidadesPorUf(string $uf): array
    {
        return cache()->remember("ibge_cidades_{$uf}", 86400, function () use ($uf): array {
            try {
                $response = Http::timeout(5)
                    ->get("https://servicodados.ibge.gov.br/api/v1/localidades/estados/{$uf}/municipios");

                if ($response->successful()) {
                    return collect($response->json())
                        ->pluck('nome', 'nome')
                        ->sort()
                        ->toArray();
                }
            } catch (\Throwable) {
                // fallback silencioso
            }

            return [];
        });
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
