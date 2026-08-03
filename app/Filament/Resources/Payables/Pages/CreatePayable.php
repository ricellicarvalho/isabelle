<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use App\Filament\Resources\Payables\Schemas\PayableForm;
use App\Models\Payable;
use App\Services\PayableRecurrenceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayable extends CreateRecord
{
    protected static string $resource = PayableResource::class;

    protected int $createdPayablesCount = 1;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['valor'] = PayableForm::parseMoney($data['valor'] ?? null) ?? 0;
        $data['valor_pago'] = PayableForm::parseMoney($data['valor_pago'] ?? null);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $isRecurring = (bool) ($data['recorrente'] ?? false);
        $endsAt = $data['data_fim_recorrencia'] ?? null;

        unset($data['recorrente'], $data['frequencia_recorrencia'], $data['data_fim_recorrencia']);

        if (! $isRecurring) {
            return Payable::create($data);
        }

        $payables = app(PayableRecurrenceService::class)->createMonthly($data, (string) $endsAt);
        $this->createdPayablesCount = $payables->count();

        return $payables->first();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return $this->createdPayablesCount > 1
            ? "{$this->createdPayablesCount} contas recorrentes criadas com sucesso"
            : 'Conta a pagar criada com sucesso';
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
