<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Read-only bridge between legacy cash records and the bank ledger. */
class FinancialCashService
{
    public static function validatePeriod(Carbon $start, Carbon $end): void
    {
        if ($start->copy()->startOfDay()->gt($end->copy()->endOfDay())) {
            throw ValidationException::withMessages(['data_fim' => 'A data final deve ser igual ou posterior à inicial.']);
        }
    }

    public static function rows(Carbon $start, Carbon $end, ?int $accountId = null): Collection
    {
        self::validatePeriod($start, $end);
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();
        $rows = collect();
        foreach ([Receivable::class => 'entrada', Payable::class => 'saida'] as $model => $type) {
            $titles = $model::with('category')->where('status', 'pago')
                ->where('data_pagamento', '<', BankMovementService::CONTROL_START)
                ->whereBetween('data_pagamento', [$start, $end])
                ->when($accountId, fn ($q) => $q->where('bank_account_id', $accountId))->get();
            foreach ($titles as $title) {
                $rows->push([
                    'id' => 'legacy:'.$type.':'.$title->id, 'data' => $title->data_pagamento,
                    'vencimento' => $title->data_vencimento, 'descricao' => $title->descricao,
                    'category_id' => $title->category_id, 'categoria' => $title->category?->descricao ?? '—',
                    'categoria_tipo' => $title->category?->tipo, 'tipo' => $type,
                    'valor' => $title->valor_pago ?? $title->valor, 'origin' => 'legacy',
                    'bank_account_id' => $title->bank_account_id,
                ]);
            }
        }
        $movements = BankMovement::with(['category', 'settlement.settleable'])
            ->where('status', 'confirmed')->where('occurred_at', '>=', BankMovementService::CONTROL_START)
            ->whereHas('bankAccount', fn ($q) => $q->where(fn ($q) => $q->whereNull('opening_balance_date')->orWhereRaw('DATE(bank_movements.occurred_at) > bank_accounts.opening_balance_date')))
            ->whereBetween('occurred_at', [$start, $end])
            ->when($accountId, fn ($q) => $q->where('bank_account_id', $accountId))
            ->orderBy('occurred_at')->orderBy('id')->get();
        foreach ($movements as $movement) {
            $rows->push([
                'id' => 'movement:'.str_pad((string) $movement->id, 20, '0', STR_PAD_LEFT), 'data' => $movement->occurred_at,
                'vencimento' => $movement->settlement?->settleable?->data_vencimento,
                'descricao' => $movement->description, 'category_id' => $movement->category_id,
                'categoria' => $movement->category?->descricao ?? '—', 'categoria_tipo' => $movement->category?->tipo,
                'tipo' => $movement->direction === 'credit' ? 'entrada' : 'saida',
                'valor' => $movement->amount, 'origin' => $movement->transfer_group ? 'transfer' : $movement->origin,
                'bank_account_id' => $movement->bank_account_id,
            ]);
        }

        return $rows->sortBy([['data', 'asc'], ['id', 'asc']])->values();
    }

    public static function titleAccount(Builder $query, ?int $accountId): Builder
    {
        return $query->when($accountId, fn ($q) => $q->where(fn ($q) => $q->where('bank_account_id', $accountId)
            ->orWhereHas('settlements', fn ($q) => $q->where('bank_account_id', $accountId)->where('status', 'confirmed'))));
    }

    public static function unclassifiedCount(Carbon $start, Carbon $end, ?int $accountId = null): int
    {
        $count = 0;
        foreach ([Payable::class, Receivable::class] as $model) {
            $count += $model::where('status', 'pago')->where('data_pagamento', '>=', BankMovementService::CONTROL_START)
                ->whereBetween('data_pagamento', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
                ->when($accountId, fn ($q) => $q->where('bank_account_id', $accountId))
                ->whereDoesntHave('settlements', fn ($q) => $q->where('status', 'confirmed'))->count();
        }

        return $count;
    }

    public static function balanceBefore(Carbon $start, ?int $accountId = null): string
    {
        $balance = '0.00';
        foreach (BankAccount::query()->when($accountId, fn ($q) => $q->whereKey($accountId))->get() as $account) {
            $balance = bcadd($balance, $account->balanceAt($start->copy()->startOfDay()->subSecond()), 2);
        }

        return $balance;
    }
}
