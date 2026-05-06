<?php

namespace App\Filament\Resources\NfseServiceCodes\Pages;

use App\Filament\Resources\NfseServiceCodes\NfseServiceCodeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNfseServiceCode extends CreateRecord
{
    protected static string $resource = NfseServiceCodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
