<?php

namespace App\Filament\Resources\SatisfactionSurveys\Pages;

use App\Filament\Resources\SatisfactionSurveys\SatisfactionSurveyResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSatisfactionSurvey extends EditRecord
{
    protected static string $resource = SatisfactionSurveyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('results')->label('Dashboard')->icon('heroicon-o-chart-bar')->url(SatisfactionSurveyResource::getUrl('results', ['record' => $this->record])),
            Action::make('open')->label('Abrir avaliação')->url($this->record->publicUrl())->openUrlInNewTab(),
            DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
