<?php

namespace App\Filament\Resources\Receivables\Pages;

use App\Filament\Resources\Receivables\ReceivableResource;
use App\Filament\Resources\Receivables\Schemas\ReceivableForm;
use App\Services\BankMovementService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditReceivable extends EditRecord
{
    protected ?bool $hasDatabaseTransactions = true;

    protected static string $resource = ReceivableResource::class;

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
            $data['valor'] = ReceivableForm::parseMoney($data['valor'] ?? null) ?? 0;
        }
        if (array_key_exists('valor_pago', $data)) {
            $data['valor_pago'] = ReceivableForm::parseMoney($data['valor_pago'] ?? null);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        app(BankMovementService::class)->syncLegacyPaid($this->record);
    }
}
