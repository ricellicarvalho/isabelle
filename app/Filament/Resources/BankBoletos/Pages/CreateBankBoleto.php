<?php

namespace App\Filament\Resources\BankBoletos\Pages;

use App\Filament\Resources\BankBoletos\BankBoletoResource;
use App\Services\BankBoletoService;
use Filament\Resources\Pages\CreateRecord;

class CreateBankBoleto extends CreateRecord
{
    protected static string $resource = BankBoletoResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        if (empty($data['nosso_numero'])) {
            $data['nosso_numero'] = BankBoletoService::generateNossoNumero();
        }

        return $data;
    }
}
