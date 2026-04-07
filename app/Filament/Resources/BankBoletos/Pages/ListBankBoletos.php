<?php

namespace App\Filament\Resources\BankBoletos\Pages;

use App\Filament\Resources\BankBoletos\BankBoletoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBankBoletos extends ListRecords
{
    protected static string $resource = BankBoletoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
