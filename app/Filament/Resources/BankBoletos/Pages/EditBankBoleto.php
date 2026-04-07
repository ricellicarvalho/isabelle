<?php

namespace App\Filament\Resources\BankBoletos\Pages;

use App\Filament\Resources\BankBoletos\BankBoletoResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBankBoleto extends EditRecord
{
    protected static string $resource = BankBoletoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
