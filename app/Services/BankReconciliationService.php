<?php

namespace App\Services;

use App\Models\BankMovement;
use App\Models\BankReconciliationAllocation;
use App\Models\BankStatementEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankReconciliationService
{
    public function reconcile(BankStatementEntry $entry, BankMovement $movement): void
    {
        $expectedDirection = (float) $entry->amount >= 0 ? 'credit' : 'debit';
        if ($entry->bank_account_id !== $movement->bank_account_id || $movement->direction !== $expectedDirection || abs(abs((float) $entry->amount) - (float) $movement->amount) > .009) {
            throw ValidationException::withMessages(['movement' => 'Conta, direção ou valor não correspondem ao lançamento bancário.']);
        }
        DB::transaction(function () use ($entry, $movement): void {
            BankReconciliationAllocation::firstOrCreate(['bank_statement_entry_id' => $entry->id, 'bank_movement_id' => $movement->id], ['amount' => abs((float) $entry->amount), 'created_by' => auth()->id()]);
            $entry->update(['status' => 'reconciled']);
        });
    }

    public function createAndReconcile(BankStatementEntry $entry, int $categoryId, ?string $description = null): BankMovement
    {
        return DB::transaction(function () use ($entry, $categoryId, $description) {
            $movement = BankMovement::create(['bank_account_id' => $entry->bank_account_id, 'category_id' => $categoryId, 'occurred_at' => $entry->occurred_at, 'direction' => (float) $entry->amount >= 0 ? 'credit' : 'debit', 'amount' => abs((float) $entry->amount), 'origin' => 'ofx', 'description' => $description ?: ($entry->memo ?: 'Lançamento OFX'), 'reference' => $entry->document, 'status' => 'confirmed', 'created_by' => auth()->id()]);
            $this->reconcile($entry, $movement);
            return $movement;
        });
    }
}
