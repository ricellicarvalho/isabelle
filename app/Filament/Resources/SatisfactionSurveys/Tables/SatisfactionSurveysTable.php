<?php

namespace App\Filament\Resources\SatisfactionSurveys\Tables;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Models\SatisfactionSurvey;
use App\Services\SatisfactionSurveyDuplicator;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
            ActionGroup::make([
                Action::make('results')->label('Dashboard')->icon('heroicon-o-chart-bar')->url(fn (SatisfactionSurvey $record) => SatisfactionSurveyResource::getUrl('results', ['record' => $record]))->openUrlInNewTab(),
                Action::make('open')->label('Abrir link')->icon('heroicon-o-arrow-top-right-on-square')->url(fn (SatisfactionSurvey $record) => $record->publicUrl())->openUrlInNewTab(),
                Action::make('copy')->label('Copiar link')->icon('heroicon-o-clipboard')
                    ->alpineClickHandler(fn (SatisfactionSurvey $record): string => '(async () => { const text = '.Js::from($record->publicUrl()).'; const fallback = () => { const input = document.createElement(\'textarea\'); input.value = text; input.style.position = \'fixed\'; input.style.opacity = \'0\'; document.body.appendChild(input); input.focus(); input.select(); document.execCommand(\'copy\'); input.remove(); }; try { if (navigator.clipboard && window.isSecureContext) { await navigator.clipboard.writeText(text); } else { fallback(); } } catch (error) { fallback(); } new FilamentNotification().title(\'Link copiado\').success().send(); })()'),
                Action::make('duplicate')
                    ->label('Duplicar')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->modalHeading('Duplicar avaliação de satisfação')
                    ->modalDescription('A nova edição copiará as configurações e perguntas, mas começará inativa e sem respostas.')
                    ->modalSubmitActionLabel('Criar nova edição')
                    ->fillForm(fn (SatisfactionSurvey $record): array => [
                        'description' => $record->description.' - Cópia',
                        'starts_at' => $record->starts_at->copy()->addYear(),
                        'ends_at' => $record->ends_at->copy()->addYear(),
                    ])
                    ->form([
                        TextInput::make('description')->label('Descrição da nova edição')->required()->maxLength(255),
                        DateTimePicker::make('starts_at')->label('Disponível a partir de')->required()->seconds(false)->displayFormat('d/m/Y H:i'),
                        DateTimePicker::make('ends_at')->label('Disponível até')->required()->seconds(false)->displayFormat('d/m/Y H:i')->after('starts_at'),
                    ])
                    ->action(function (array $data, SatisfactionSurvey $record) {
                        $copy = app(SatisfactionSurveyDuplicator::class)->duplicate(
                            $record,
                            $data['description'],
                            Carbon::parse($data['starts_at']),
                            Carbon::parse($data['ends_at']),
                            auth()->id(),
                        );

                        Notification::make()
                            ->title('Nova edição criada')
                            ->body('A pesquisa foi duplicada como inativa. Revise-a e ative quando estiver pronta.')
                            ->success()
                            ->send();

                        return redirect()->to(SatisfactionSurveyResource::getUrl('edit', ['record' => $copy]));
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ]),
        ]);
    }
}
