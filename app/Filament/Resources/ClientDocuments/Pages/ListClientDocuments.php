<?php

namespace App\Filament\Resources\ClientDocuments\Pages;

use App\Filament\Resources\ClientDocuments\ClientDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListClientDocuments extends ListRecords
{
    protected static string $resource = ClientDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
