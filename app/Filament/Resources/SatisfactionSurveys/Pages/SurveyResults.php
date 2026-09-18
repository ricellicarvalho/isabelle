<?php

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use App\Services\SurveyResultsService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class SurveyResults extends Page
{
    use InteractsWithRecord;

    protected static string $resource = SatisfactionSurveyResource::class;

    protected string $view = 'filament.resources.satisfaction-surveys.results';

    public array $summary = [];

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->summary = SurveyResultsService::summarize($this->getRecord());
    }

    public function getTitle(): string
    {
        return 'Resultados — '.$this->getRecord()->description;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('edit')->label('Configurar avaliação')->url(SatisfactionSurveyResource::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('open')->label('Abrir link público')->url($this->getRecord()->publicUrl())->openUrlInNewTab(),
        ];
    }
}
