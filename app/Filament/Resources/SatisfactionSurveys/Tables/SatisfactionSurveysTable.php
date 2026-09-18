<?php

namespace App\Filament\Resources\SatisfactionSurveys\Tables;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Models\SatisfactionSurvey;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Support\Enums\Alignment;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

class SatisfactionSurveysTable
{
    public static function configure(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('description')->label('Descrição')->searchable()->wrap(),
            TextColumn::make('response_type')->label('Tipo')->badge()->alignment(Alignment::Center)->toggleable(isToggledHiddenByDefault: true)->formatStateUsing(fn ($state) => $state->label()),
            TextColumn::make('status')->label('Situação')->state(fn (SatisfactionSurvey $record) => $record->statusLabel())->badge()
                ->alignment(Alignment::Center)
                ->color(fn (string $state) => match ($state) {
                    'No ar' => 'success', 'Agendada' => 'info', 'Encerrada' => 'gray', default => 'warning'
                }),
            TextColumn::make('period')->label('Período')->alignment(Alignment::Center)
                ->state(fn (SatisfactionSurvey $record): HtmlString => new HtmlString(
                    '<span style="display:inline-flex;min-width:142px;flex-direction:column;align-items:center;gap:2px;padding:7px 11px;border:1px solid #d8b4fe;border-radius:9px;background:linear-gradient(135deg,#faf5ff,#fff);box-shadow:0 2px 7px rgba(124,58,237,.08);color:#4c1d95;font-weight:700;line-height:1.25">'
                    .e($record->starts_at->format('d/m/Y H:i'))
                    .'<span style="color:#a78bfa;font-size:10px;font-weight:800;text-transform:uppercase">até</span>'
                    .e($record->ends_at->format('d/m/Y H:i'))
                    .'</span>'
                )),
            TextColumn::make('submissions_count')->counts('submissions')->label('Respostas')->alignment(Alignment::Center)->sortable(),
        ])->actions([
            Action::make('results')->label('Dashboard')->icon('heroicon-o-chart-bar')->url(fn (SatisfactionSurvey $record) => SatisfactionSurveyResource::getUrl('results', ['record' => $record])),
            Action::make('open')->label('Abrir link')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (SatisfactionSurvey $record) => $record->publicUrl())->openUrlInNewTab(),
            Action::make('copy')->label('Copiar link')->icon('heroicon-o-clipboard')
                ->alpineClickHandler(fn (SatisfactionSurvey $record): string => '(() => { const text = '.Js::from($record->publicUrl()).'; const fallback = () => { const input = document.createElement(\'textarea\'); input.value = text; input.style.position = \'fixed\'; input.style.opacity = \'0\'; document.body.appendChild(input); input.focus(); input.select(); document.execCommand(\'copy\'); input.remove(); }; if (navigator.clipboard && window.isSecureContext) { navigator.clipboard.writeText(text).catch(fallback); } else { fallback(); } event.currentTarget.setAttribute(\'title\', \'Link copiado\'); })()'),
            EditAction::make(), DeleteAction::make(),
        ]);
    }
}
