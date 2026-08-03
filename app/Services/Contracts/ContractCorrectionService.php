<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ContractVersionChange;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ContractCorrectionService
{
    public function correct(Contract $contract, array $data, int $userId): ContractVersion
    {
        $data = Validator::make($data, [
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'observacoes' => ['nullable', 'string'],
            'change_reason' => ['required', 'string', 'max:5000'],
        ], [
            'change_reason.required' => 'Informe o motivo da correção.',
        ])->validate();

        return DB::transaction(function () use ($contract, $data, $userId): ContractVersion {
            /** @var Contract $lockedContract */
            $lockedContract = Contract::query()->lockForUpdate()->findOrFail($contract->getKey());

            if ($lockedContract->status === 'cancelado') {
                throw ValidationException::withMessages([
                    'contract' => 'Contratos cancelados não podem ser corrigidos.',
                ]);
            }

            /** @var ContractVersion|null $previous */
            $previous = $lockedContract->currentVersion()->lockForUpdate()->first();
            if (! $previous) {
                throw ValidationException::withMessages([
                    'contract' => 'O contrato não possui uma versão atual para preservar no histórico.',
                ]);
            }

            $start = Carbon::parse($data['data_inicio'])->startOfDay();
            $end = Carbon::parse($data['data_fim'])->startOfDay();
            $datesChanged = ! $lockedContract->data_inicio->isSameDay($start)
                || ! $lockedContract->data_fim->isSameDay($end);

            $lastDueDate = $start->copy()->addMonthsNoOverflow(max(0, (int) $lockedContract->quantidade_parcelas - 1));
            if ($lastDueDate->gt($end)) {
                throw ValidationException::withMessages([
                    'data_fim' => 'A nova vigência não comporta todas as parcelas do contrato.',
                ]);
            }

            $currentReceivables = $lockedContract->receivables()
                ->where('contract_version_id', $previous->id);

            if ($datesChanged && (clone $currentReceivables)->where('status', 'pago')->exists()) {
                throw ValidationException::withMessages([
                    'data_inicio' => 'A vigência não pode ser corrigida porque esta versão possui parcela paga.',
                ]);
            }

            if ($datesChanged && (clone $currentReceivables)->whereHas('bankBoletos')->exists()) {
                throw ValidationException::withMessages([
                    'data_inicio' => 'Cancele os boletos vinculados antes de corrigir a vigência.',
                ]);
            }

            if ($datesChanged && (clone $currentReceivables)->whereHas('nfses')->exists()) {
                throw ValidationException::withMessages([
                    'data_inicio' => 'A vigência não pode ser corrigida enquanto houver nota fiscal vinculada.',
                ]);
            }

            $values = $lockedContract->only(ContractRenewalService::VERSIONED_FIELDS);
            $values['data_inicio'] = $start->toDateString();
            $values['data_fim'] = $end->toDateString();
            $values['observacoes'] = $data['observacoes'] ?? null;

            $previous->update(['status' => 'superseded']);

            $version = $lockedContract->versions()->create($values + [
                'previous_version_id' => $previous->id,
                'version_number' => $previous->version_number + 1,
                'change_type' => 'correction',
                'status' => 'active',
                'change_reason' => $data['change_reason'],
                'activated_at' => now(),
                'created_by' => $userId,
                'activated_by' => $userId,
            ]);

            foreach (['data_inicio', 'data_fim', 'observacoes'] as $field) {
                $oldValue = $this->comparableValue($previous->getAttribute($field));
                $newValue = $this->comparableValue($version->getAttribute($field));
                if ($oldValue === $newValue) {
                    continue;
                }

                ContractVersionChange::create([
                    'contract_version_id' => $version->id,
                    'field' => $field,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'created_by' => $userId,
                ]);
            }

            $pendingReceivables = (clone $currentReceivables)
                ->whereIn('status', ['pendente', 'vencido'])
                ->lockForUpdate()
                ->get();

            foreach ($pendingReceivables as $receivable) {
                $dueDate = $start->copy()->addMonthsNoOverflow(max(0, (int) $receivable->numero_parcela - 1));
                $receivable->update([
                    'contract_version_id' => $version->id,
                    'data_vencimento' => $dueDate,
                    'status' => $dueDate->lt(today()) ? 'vencido' : 'pendente',
                ]);
            }

            $status = $lockedContract->status;
            if ($status !== 'rascunho') {
                $status = $end->lt(today()) ? 'finalizado' : 'ativo';
            }

            Contract::withoutEvents(fn () => $lockedContract->update([
                'data_inicio' => $start,
                'data_fim' => $end,
                'observacoes' => $data['observacoes'] ?? null,
                'current_version_id' => $version->id,
                'status' => $status,
            ]));

            return $version->fresh(['changes']);
        }, 3);
    }

    private function comparableValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : (string) $value;
    }
}
