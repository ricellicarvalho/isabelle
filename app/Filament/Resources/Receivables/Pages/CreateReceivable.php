<?php

namespace App\Filament\Resources\Receivables\Pages;

use App\Filament\Resources\Receivables\ReceivableResource;
use App\Filament\Resources\Receivables\Schemas\ReceivableForm;
use Filament\Resources\Pages\CreateRecord;

class CreateReceivable extends CreateRecord
{
    protected static string $resource = ReceivableResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['valor'] = ReceivableForm::parseMoney($data['valor'] ?? null) ?? 0;
        $data['valor_pago'] = ReceivableForm::parseMoney($data['valor_pago'] ?? null);

        return $data;
    }
}
