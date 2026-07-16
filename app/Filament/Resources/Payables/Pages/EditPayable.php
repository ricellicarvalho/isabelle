<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\Payables\Schemas\PayableForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayable extends EditRecord
{
    protected static string $resource = PayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['valor'] = PayableForm::parseMoney($data['valor'] ?? null) ?? 0;
        $data['valor_pago'] = PayableForm::parseMoney($data['valor_pago'] ?? null);

        return $data;
    }
}
