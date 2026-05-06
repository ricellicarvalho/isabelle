<?php

namespace App\Filament\Resources\NfseConfigs\Pages;

use App\Filament\Resources\NfseConfigs\NfseConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNfseConfig extends CreateRecord
{
    protected static string $resource = NfseConfigResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
