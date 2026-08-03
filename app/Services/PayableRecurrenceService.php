<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\PayableRecurrence;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayableRecurrenceService
{
    /**
     * @return Collection<int, Payable>
     */
    public function createMonthly(array $payableData, string $endsAt): Collection
    {
        $startsAt = Carbon::parse($payableData['data_vencimento'])->startOfDay();
        $endsAtDate = Carbon::parse($endsAt)->endOfDay();

        if ($endsAtDate->lt($startsAt)) {
            throw ValidationException::withMessages([
                'data.data_fim_recorrencia' => 'O último vencimento deve ser igual ou posterior ao primeiro vencimento.',
            ]);
        }

        $dates = collect();
        for ($index = 0; $index < 120; $index++) {
            $date = $startsAt->copy()->addMonthsNoOverflow($index);
            if ($date->gt($endsAtDate)) {
                break;
            }

            $dates->push($date);
        }

        if ($dates->isEmpty()) {
            throw ValidationException::withMessages([
                'data.data_fim_recorrencia' => 'O período informado não gera nenhuma conta.',
            ]);
        }

        if ($dates->count() === 120 && $startsAt->copy()->addMonthsNoOverflow(120)->lte($endsAtDate)) {
            throw ValidationException::withMessages([
                'data.data_fim_recorrencia' => 'Uma recorrência pode gerar no máximo 120 contas.',
            ]);
        }

        return DB::transaction(function () use ($payableData, $startsAt, $endsAtDate, $dates): Collection {
            $recurrence = PayableRecurrence::create([
                'frequency' => 'monthly',
                'starts_at' => $startsAt,
                'ends_at' => $endsAtDate,
                'occurrences_count' => $dates->count(),
                'created_by' => $payableData['created_by'],
            ]);

            return $dates->values()->map(function (Carbon $dueDate, int $index) use ($payableData, $recurrence, $dates): Payable {
                return Payable::create([
                    ...$payableData,
                    'payable_recurrence_id' => $recurrence->id,
                    'recurrence_sequence' => $index + 1,
                    'recurrence_total' => $dates->count(),
                    'data_vencimento' => $dueDate,
                    'data_pagamento' => null,
                    'valor_pago' => null,
                    'status' => 'pendente',
                ]);
            });
        });
    }
}
