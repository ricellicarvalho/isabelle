<?php

namespace App\Filament\Resources\NfseConfigs\Pages;

use App\Filament\Resources\NfseConfigs\NfseConfigResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNfseConfig extends EditRecord
{
    protected static string $resource = NfseConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
