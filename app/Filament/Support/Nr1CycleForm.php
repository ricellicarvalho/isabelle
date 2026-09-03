<?php

namespace App\Filament\Support;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Nr1Cycle;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;

class Nr1CycleForm
{
    public static function schema(): array
    {
        return [
            Section::make('Vínculo com o contrato')
                ->description('O ano identifica a NR-1. A versão mantém apenas o vínculo histórico da vigência.')
                ->icon(Heroicon::Link)
                ->columns(2)
                ->schema([
                    Select::make('reference_year')->label('Ano de referência')
                        ->options(fn (): array => collect(range((int) now()->format('Y') + 2, 2000))->mapWithKeys(fn (int $year): array => [$year => (string) $year])->all())
                        ->required()->disabled(fn ($record): bool => $record instanceof Nr1Cycle && filled($record->contract_id))->dehydrated(),
                    Select::make('contract_id')->label('Contrato NR-1')
                        ->options(function ($record): array {
                            $clientId = $record instanceof Nr1Cycle || $record instanceof Contract
                                ? $record->client_id
                                : null;

                            return Contract::query()->where('client_id', $clientId)->where('tipo_servico', 'nr1')->orderBy('numero')->pluck('numero', 'id')->all();
                        })
                        ->searchable()->native(false)->live()
                        ->afterStateUpdated(fn (Set $set) => $set('opened_by_contract_version_id', null)),
                    Select::make('opened_by_contract_version_id')->label('Versão que abriu o ciclo')
                        ->options(fn (Get $get): array => ContractVersion::query()
                            ->where('contract_id', $get('contract_id'))->whereIn('change_type', ['original', 'renewal'])
                            ->orderBy('version_number')->get()->mapWithKeys(fn (ContractVersion $version): array => [
                                $version->id => "v{$version->version_number} · {$version->data_inicio->format('d/m/Y')} a {$version->data_fim->format('d/m/Y')}",
                            ])->all())
                        ->searchable()->native(false)->columnSpanFull()
                        ->visible(fn (Get $get): bool => filled($get('contract_id'))),
                ]),
            Section::make('Status de Conformidade')
                ->icon(Heroicon::ShieldCheck)->iconColor('primary')->compact()->columns(2)
                ->schema([
                    Select::make('status')->label('Status NR-1')
                        ->options(['pendente' => 'Pendente', 'em_andamento' => 'Em Andamento', 'regularizada' => 'Regularizada', 'finalizada' => 'Finalizada'])
                        ->disabled()->dehydrated(false)->native(false)
                        ->helperText('Calculado automaticamente conforme as etapas.'),
                    Placeholder::make('nr1_progresso')->label('Progresso')
                        ->content(fn (Get $get): HtmlString => self::progress($get)),
                ]),
            Section::make('Checklist de Conformidade NR-1')
                ->description('Marque cada etapa conforme concluída. Etapas 1-3 = Em Andamento | 1-4 = Regularizada | 1-5 = Finalizada.')
                ->icon(Heroicon::ListBullet)->iconColor('primary')->columns(1)
                ->schema([
                    self::step('etapa1', 'Etapa 1: Encontro', Heroicon::UserGroup, true),
                    self::step('etapa2', 'Etapa 2: Avaliação dos Riscos Psicossociais', Heroicon::MagnifyingGlass),
                    self::step('etapa3', 'Etapa 3: Relatório Diagnóstico (DPRS)', Heroicon::DocumentChartBar),
                    self::step('etapa4', 'Etapa 4: Matriz de Risco (Segurança do Trabalho)', Heroicon::ShieldExclamation),
                    self::step('etapa5', 'Etapa 5: Devolutiva', Heroicon::ChatBubbleLeftRight),
                ]),
            Section::make('Observações')->schema([
                Textarea::make('notes')->label('Observações')->rows(4)->columnSpanFull(),
            ]),
        ];
    }

    private static function step(string $key, string $label, Heroicon $icon, bool $withType = false): Section
    {
        $fields = [
            Toggle::make("checklist.{$key}")
                ->label(new HtmlString('<span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success-600" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 111.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>Concluída</span>'))
                ->live()->afterStateUpdated(fn (Get $get, Set $set) => self::recalculateStatus($get, $set)),
        ];
        if ($withType) {
            $fields[] = Select::make("checklist.{$key}_tipo")->label('Modalidade')
                ->options(['presencial' => 'Presencial', 'online' => 'Online'])->native(false)->placeholder('Selecione');
        }
        $fields[] = DatePicker::make("checklist.{$key}_data")->label('Data')->native(false)->displayFormat('d/m/Y');

        return Section::make($label)->icon($icon)
            ->iconColor(fn (Get $get): string => ! empty($get("checklist.{$key}")) ? 'success' : 'gray')
            ->compact()->columns($withType ? 3 : 2)->schema($fields);
    }

    private static function recalculateStatus(Get $get, Set $set): void
    {
        $set('status', Nr1Cycle::statusFromChecklist($get('checklist') ?? []));
    }

    private static function progress(Get $get): HtmlString
    {
        $checklist = $get('checklist') ?? [];
        $steps = ['etapa1', 'etapa2', 'etapa3', 'etapa4', 'etapa5'];
        $done = count(array_filter($steps, fn (string $step): bool => ! empty($checklist[$step])));
        $percentage = (int) round(($done / count($steps)) * 100);
        $color = match (true) { $percentage === 100 => '#22c55e', $percentage >= 60 => '#8b5cf6', $percentage > 0 => '#f59e0b', default => '#e5e7eb' };

        return new HtmlString('<div style="margin-top:6px"><div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">'
            .'<span style="font-size:1.25rem;font-weight:700;color:'.$color.'">'.$percentage.'%</span><span style="color:#6b7280;font-size:.85rem">'.$done.' de 5 etapas</span></div>'
            .'<div style="width:100%;height:8px;background:#e5e7eb;border-radius:9999px;overflow:hidden"><div style="width:'.$percentage.'%;height:100%;background:'.$color.';border-radius:9999px;transition:width .4s ease"></div></div></div>');
    }
}
