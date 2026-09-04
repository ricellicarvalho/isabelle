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

    public function syncLegacyPaid(Model $title): ?FinancialSettlement
    {
        if (! $title instanceof Payable && ! $title instanceof Receivable) return null;
        if ($title->status !== 'pago' || ! $title->bank_account_id || ! $title->data_pagamento || $title->data_pagamento->lt(self::CONTROL_START)) return null;

        return DB::transaction(function () use ($title) {
            $settlement = FinancialSettlement::firstOrNew([
                'settleable_type' => $title::class,
                'settleable_id' => $title->id,
                'origin' => 'legacy_migration',
            ]);
            $settlement->fill([
                'bank_account_id' => $title->bank_account_id,
                'settled_at' => $title->data_pagamento,
                'amount' => $title->valor_pago ?: $title->valor,
                'payment_method' => $title->forma_pagamento,
                'reference' => (string) $title->id,
                'status' => 'confirmed',
                'created_by' => auth()->id() ?: $title->created_by,
            ])->save();
            $this->syncMovement($settlement, $title);
            return $settlement;
        });
    }

    public function settle(Model $title, BankAccount $account, string $date, string|float $amount, ?string $method = null): FinancialSettlement
    {
        $paid = (float) $title->settlements()->where('status', 'confirmed')->sum('amount');
        if ((float) $amount <= 0 || $paid + (float) $amount > (float) $title->valor + .001) {
            throw ValidationException::withMessages(['amount' => 'O valor da baixa é inválido ou superior ao saldo em aberto.']);
        }
        return DB::transaction(function () use ($title, $account, $date, $amount, $method) {
            $settlement = $title->settlements()->create([
                'bank_account_id' => $account->id, 'settled_at' => $date, 'amount' => $amount,
                'payment_method' => $method, 'origin' => 'manual', 'status' => 'confirmed', 'created_by' => auth()->id(),
            ]);
            $this->syncMovement($settlement, $title);
            $total = (float) $title->settlements()->where('status', 'confirmed')->sum('amount');
            $title->updateQuietly(['bank_account_id' => $account->id, 'valor_pago' => $total, 'data_pagamento' => $date, 'forma_pagamento' => $method, 'status' => $total >= (float) $title->valor ? 'pago' : $title->status]);
            return $settlement;
        });
    }

    public function transfer(BankAccount $from, BankAccount $to, string $date, string|float $amount, ?string $description = null): array
    {
        if ($from->is($to) || (float) $amount <= 0) throw ValidationException::withMessages(['amount' => 'Informe contas diferentes e um valor positivo.']);
        return DB::transaction(function () use ($from, $to, $date, $amount, $description) {
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
            'counterparty' => $title instanceof Receivable ? $title->client?->nome : ($title->supplier?->nome ?: $title->fornecedor),
            'reference' => $settlement->reference,
            'status' => $settlement->status,
            'created_by' => $settlement->created_by,
        ]);
    }
}
