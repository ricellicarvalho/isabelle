<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\Payables\Schemas\PayableForm;
use App\Services\BankMovementService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPayable extends EditRecord
{
    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = PayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \App\Filament\Actions\FinancialSettlementActions::settle(),
            \App\Filament\Actions\FinancialSettlementActions::reverse(),
            \App\Filament\Actions\FinancialSettlementActions::history(),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (array_key_exists('valor', $data)) {
            $data['valor'] = PayableForm::parseMoney($data['valor'] ?? null) ?? 0;
        }
        if (array_key_exists('valor_pago', $data)) {
            $data['valor_pago'] = PayableForm::parseMoney($data['valor_pago'] ?? null);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(BankMovementService::class)->syncLegacyPaid($this->record);
    }
}
