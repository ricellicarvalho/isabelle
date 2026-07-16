<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\Payables\Schemas\PayableForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['valor'] = PayableForm::parseMoney($data['valor'] ?? null) ?? 0;
        $data['valor_pago'] = PayableForm::parseMoney($data['valor_pago'] ?? null);

        return $data;
    }
}
