<?php

namespace App\Filament\Resources\ClientDocuments\Pages;

use App\Filament\Resources\ClientDocuments\ClientDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateClientDocument extends CreateRecord
{
    protected static string $resource = ClientDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
