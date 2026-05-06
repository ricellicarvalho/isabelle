<?php

namespace App\Filament\Resources\NfseConfigs\Pages;

use App\Filament\Resources\NfseConfigs\NfseConfigResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNfseConfigs extends ListRecords
{
    protected static string $resource = NfseConfigResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
