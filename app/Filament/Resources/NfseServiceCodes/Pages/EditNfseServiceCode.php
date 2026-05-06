<?php

namespace App\Filament\Resources\NfseServiceCodes\Pages;

use App\Filament\Resources\NfseServiceCodes\NfseServiceCodeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNfseServiceCode extends EditRecord
{
    protected static string $resource = NfseServiceCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
