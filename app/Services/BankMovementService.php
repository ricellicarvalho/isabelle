<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\FinancialSettlement;
use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BankMovementService
{
    public const CONTROL_START = '2026-08-01';

    public static function money(mixed $value, string $field = 'amount'): string
    {
        $value = (string) $value;
        if (! preg_match('/^\d{1,12}(\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages([$field => 'Informe um valor positivo com até duas casas decimais.']);
        }

        return bcadd($value, '0', 2);
    }

    public function openAmount(Model $title): string
    {
        $query = $title->settlements();
        // A caller may already have opened a REPEATABLE READ snapshot (bulk actions).
        // Locking reads must see settlements committed while waiting for the title lock.
        if (DB::transactionLevel() > 0) {
            $query->lockForUpdate();
        }
        $settlements = $query->get();
        if ($settlements->isEmpty() && $title->status === 'pago') {
            return '0.00';
        }
        $paid = '0.00';
        foreach ($settlements->where('status', 'confirmed') as $settlement) {
            $paid = bcadd($paid, $settlement->principal_amount ?? $settlement->amount, 2);
        }

        return bcsub($title->valor, $paid, 2);
    }

    public function syncLegacyPaid(Model $title): ?FinancialSettlement
    {
        if (! $title instanceof Payable && ! $title instanceof Receivable) {
            return null;
        }

        return DB::transaction(function () use ($title) {
            $title = $title->newQuery()->lockForUpdate()->findOrFail($title->id);
            if ($title->settlements()->exists()) {
                return $title->settlements()->first();
            }
            if ($title->status !== 'pago' || ! $title->bank_account_id || ! $title->data_pagamento || $title->data_pagamento->lt(self::CONTROL_START)) {
                return null;
            }

            return $this->registerLegacyPaid($title, $title->bankAccount);
        });
    }

    public function registerLegacyPaid(Model $title, BankAccount $account): FinancialSettlement
    {
        if (! $title instanceof Payable && ! $title instanceof Receivable) {
            throw ValidationException::withMessages(['records' => 'Selecione somente contas a pagar ou a receber.']);
        }

        return DB::transaction(function () use ($title, $account): FinancialSettlement {
            $title = $title->newQuery()->lockForUpdate()->findOrFail($title->id);
            if ($existing = $title->settlements()->first()) {
                return $existing;
            }
            if ($title->status !== 'pago' || ! $title->data_pagamento || $title->data_pagamento->lt(self::CONTROL_START)) {
                throw ValidationException::withMessages(['records' => 'A seleção contém título que não é uma baixa histórica válida desde 01/08/2026.']);
            }

            return $this->settle(
                $title,
                $account,
                $title->data_pagamento->toDateString(),
                $title->valor_pago ?: $title->valor,
                $title->forma_pagamento,
                ['origin' => 'legacy_migration', 'idempotency_key' => 'legacy:'.$title->getMorphClass().':'.$title->id],
            );
        }, 3);
    }

    public function settle(Model $title, BankAccount $account, string $date, string $amount, ?string $method = null, array $adjustments = []): FinancialSettlement
    {
        return DB::transaction(function () use ($title, $account, $date, $amount, $method, $adjustments) {
            $title = $title->newQuery()->lockForUpdate()->findOrFail($title->id);
            $key = $adjustments['idempotency_key'] ?? null;
            if ($key && ($existing = FinancialSettlement::where('idempotency_key', $key)->lockForUpdate()->first())) {
                if ($existing->settleable_type !== $title->getMorphClass() || $existing->settleable_id !== $title->id) {
                    throw ValidationException::withMessages(['amount' => 'Identificador de baixa já utilizado.']);
                }
                if ($existing->status !== 'confirmed' || $existing->bank_account_id !== $account->id
                    || $existing->settled_at->toDateString() !== \Illuminate\Support\Carbon::parse($date)->toDateString()
                    || bccomp($existing->principal_amount, self::money($amount), 2) !== 0) {
                    throw ValidationException::withMessages(['amount' => 'Esta solicitação já foi processada com outros dados ou estornada.']);
                }
                foreach (['interest', 'penalty', 'discount', 'fee'] as $field) {
                    if (bccomp($existing->$field, self::money($adjustments[$field] ?? '0', $field), 2) !== 0) {
                        throw ValidationException::withMessages([$field => 'Esta solicitação já foi processada com outros valores.']);
                    }
                }

                return $existing;
            }
            $account = BankAccount::query()->lockForUpdate()->findOrFail($account->id);
            $date = \Illuminate\Support\Carbon::parse($date);
            if (! $account->ativo || $date->lt(self::CONTROL_START) || ($account->opening_balance_date && $date->startOfDay()->lte($account->opening_balance_date))) {
                throw ValidationException::withMessages(['bank_account_id' => 'Use uma conta ativa e uma data após o saldo inicial, desde 01/08/2026.']);
            }
            $principal = self::money($amount);
            $origin = $adjustments['origin'] ?? 'manual';
            $open = $origin === 'legacy_migration' && ! $title->settlements()->exists() ? $title->valor : $this->openAmount($title);
            if ($title->status === 'cancelado' || bccomp($principal, '0', 2) <= 0 || bccomp($principal, $open, 2) > 0) {
                throw ValidationException::withMessages(['amount' => 'O valor principal é inválido ou superior ao saldo em aberto.']);
            }
            $values = [];
            foreach (['interest', 'penalty', 'discount', 'fee'] as $field) {
                $values[$field] = self::money($adjustments[$field] ?? '0', $field);
            }
            if (bccomp($values['discount'], $principal, 2) > 0) {
                throw ValidationException::withMessages(['discount' => 'O desconto não pode superar o principal.']);
            }
            $cash = bcsub(bcadd(bcadd($principal, $values['interest'], 2), $values['penalty'], 2), $values['discount'], 2);
            $cash = $title instanceof Receivable ? bcsub($cash, $values['fee'], 2) : bcadd($cash, $values['fee'], 2);
            if (bccomp($cash, '0', 2) <= 0) {
                throw ValidationException::withMessages(['amount' => 'O valor efetivamente movimentado deve ser positivo.']);
            }
            $cash = self::money($cash);
            $settlement = $title->settlements()->create($values + [
                'bank_account_id' => $account->id, 'settled_at' => $date, 'amount' => $cash, 'principal_amount' => $principal,
                'payment_method' => $method, 'origin' => $origin, 'status' => 'confirmed', 'created_by' => auth()->id() ?: $title->created_by,
                'idempotency_key' => $key, 'reference' => $adjustments['reference'] ?? (string) $title->id, 'notes' => $adjustments['notes'] ?? null,
            ]);
            $this->syncMovement($settlement, $title);
            $this->refreshSummary($title);

            return $settlement;
        }, 3);
    }

    public function reverse(FinancialSettlement $settlement, string $reason): void
    {
        if (! auth()->id() || trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Informe o responsável e a justificativa do estorno.']);
        }
        DB::transaction(function () use ($settlement, $reason) {
            $title = $settlement->settleable()->firstOrFail();
            $title = $title->newQuery()->lockForUpdate()->findOrFail($title->id);
            $settlement = FinancialSettlement::query()->lockForUpdate()->findOrFail($settlement->id);
            if ($settlement->status === 'reversed') {
                return;
            }
            $movement = $settlement->movement()->lockForUpdate()->firstOrFail();
            if (\App\Models\BankReconciliationAllocation::where('bank_movement_id', $movement->id)->whereNull('reversed_at')->exists()) {
                throw ValidationException::withMessages(['reason' => 'Desfaça a conciliação antes de estornar esta baixa.']);
            }
            $settlement->update(['status' => 'reversed', 'reversed_at' => now(), 'reversed_by' => auth()->id(), 'reversal_reason' => trim($reason)]);
            $movement->updateQuietly(['status' => 'reversed']);
            $this->refreshSummary($title);
        }, 3);
    }

    private function refreshSummary(Model $title): void
    {
        $settlements = $title->settlements()->where('status', 'confirmed')->orderBy('settled_at')->orderBy('id')->lockForUpdate()->get();
        $last = $settlements->last();
        $total = '0.00';
        foreach ($settlements as $settlement) {
            $total = bcadd($total, $settlement->amount, 2);
        }
        $title->newQuery()->whereKey($title->id)->update([
            'bank_account_id' => $last?->bank_account_id, 'valor_pago' => $total,
            'data_pagamento' => $last?->settled_at, 'forma_pagamento' => $last?->payment_method,
            'status' => bccomp($this->openAmount($title), '0', 2) === 0 ? 'pago' : ($title->data_vencimento->lt(today()) ? 'vencido' : 'pendente'),
        ]);
    }

    public function transfer(BankAccount $from, BankAccount $to, string $date, string $amount, ?string $description = null): array
    {
        if ($from->is($to) || bccomp(self::money($amount), '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Informe contas diferentes e um valor positivo.']);
        }

        return DB::transaction(function () use ($from, $to, $date, $amount, $description) {
            $accounts = BankAccount::whereKey([$from->id, $to->id])->orderBy('id')->lockForUpdate()->get();
            if ($accounts->count() !== 2 || $accounts->contains(fn ($account) => ! $account->ativo)) {
                throw ValidationException::withMessages(['amount' => 'Use contas ativas para transferir.']);
            }
            $group = (string) Str::uuid();
            $common = ['occurred_at' => $date, 'amount' => $amount, 'origin' => 'transfer', 'description' => $description ?: 'Transferência entre contas', 'transfer_group' => $group, 'status' => 'confirmed', 'created_by' => auth()->id()];

            return [BankMovement::create($common + ['bank_account_id' => $from->id, 'direction' => 'debit', 'counterparty' => $to->display_name]), BankMovement::create($common + ['bank_account_id' => $to->id, 'direction' => 'credit', 'counterparty' => $from->display_name])];
        });
    }

    private function syncMovement(FinancialSettlement $settlement, Model $title): void
    {
        BankMovement::updateOrCreate(['financial_settlement_id' => $settlement->id], [
            'bank_account_id' => $settlement->bank_account_id,
            'category_id' => $title->category_id,
            'occurred_at' => $settlement->settled_at,
            'direction' => $title instanceof Receivable ? 'credit' : 'debit',
            'amount' => $settlement->amount,
            'origin' => $title instanceof Receivable ? 'receivable' : 'payable',
            'description' => $title->descricao,
            'counterparty' => $title instanceof Receivable ? $title->client?->razao_social : ($title->supplier?->nome ?: $title->fornecedor),
            'reference' => $settlement->reference,
            'status' => $settlement->status,
            'created_by' => $settlement->created_by,
        ]);
    }
}
