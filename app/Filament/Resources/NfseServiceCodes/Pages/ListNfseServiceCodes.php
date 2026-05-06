<?php

namespace App\Filament\Resources\NfseServiceCodes\Pages;

use App\Filament\Resources\NfseServiceCodes\NfseServiceCodeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNfseServiceCodes extends ListRecords
{
    protected static string $resource = NfseServiceCodeResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
