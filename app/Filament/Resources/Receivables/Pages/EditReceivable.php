<?php

namespace App\Filament\Resources\Receivables\Pages;

use App\Filament\Resources\Receivables\ReceivableResource;
use App\Filament\Resources\Receivables\Schemas\ReceivableForm;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReceivable extends EditRecord
{
    protected static string $resource = ReceivableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['valor'] = ReceivableForm::parseMoney($data['valor'] ?? null) ?? 0;
        $data['valor_pago'] = ReceivableForm::parseMoney($data['valor_pago'] ?? null);

        return $data;
    }
}
