<?php

namespace App\Filament\Resources\Nfses\Pages;

use App\Filament\Resources\Nfses\NfseResource;
use Filament\Resources\Pages\ListRecords;

class ListNfses extends ListRecords
{
    protected static string $resource = NfseResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
