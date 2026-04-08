<?php

namespace App\Filament\Resources\ClientDocuments\Pages;

use App\Filament\Resources\ClientDocuments\ClientDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClientDocument extends EditRecord
{
    protected static string $resource = ClientDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
