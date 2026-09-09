<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankReconciliationAllocation;
use App\Models\BankStatementEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BankReconciliationService
{
    /** @return Collection<int, array{movement: BankMovement, remaining: string, score: int, reasons: array}> */
    public function suggestions(BankStatementEntry $entry, int $days = 10): Collection
    {
        $direction = bccomp($entry->amount, '0', 2) >= 0 ? 'credit' : 'debit';
        $amount = $this->entryRemaining($entry);

        return BankMovement::query()->with('activeReconciliationAllocations')
            ->where('bank_account_id', $entry->bank_account_id)->where('status', 'confirmed')->where('direction', $direction)
            ->whereBetween('occurred_at', [$entry->occurred_at->copy()->subDays($days), $entry->occurred_at->copy()->addDays($days)])
            ->get()->map(function (BankMovement $movement) use ($entry, $amount): array {
                $remaining = $this->movementRemaining($movement);
                $dateDistance = (int) $entry->occurred_at->copy()->startOfDay()->diffInDays($movement->occurred_at->copy()->startOfDay());
                $reasons = [];
                $score = max(0, 30 - ($dateDistance * 3));
                if (bccomp($remaining, $amount, 2) === 0) {
                    $score += 60;
                    $reasons[] = 'valor exato';
                } elseif (bccomp($remaining, '0', 2) > 0) {
                    $score += 10;
                    $reasons[] = 'permite alocação parcial';
                }
                if ($this->contains($entry->document, $movement->reference)) {
                    $score += 35;
                    $reasons[] = 'documento';
                }
                if ($this->contains($entry->memo, $movement->counterparty) || $this->contains($entry->memo, $movement->description)) {
                    $score += 20;
                    $reasons[] = 'histórico/favorecido';
                }
                $reasons[] = $dateDistance === 0 ? 'mesma data' : "{$dateDistance} dia(s)";

                return compact('movement', 'remaining', 'score', 'reasons');
            })->filter(fn (array $item): bool => bccomp($item['remaining'], '0', 2) > 0)
            ->sortByDesc('score')->values();
    }

    public function allocate(BankStatementEntry $entry, array $allocations): void
    {
        DB::transaction(function () use ($entry, $allocations): void {
            $entry = BankStatementEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== 'pending') {
                throw ValidationException::withMessages(['allocations' => 'Somente linhas pendentes aceitam alocações.']);
            }
            $grouped = collect($allocations)->groupBy('movement_id')->map(fn ($items) => $items->reduce(
                fn (string $sum, array $item): string => bcadd($sum, BankMovementService::money($item['amount']), 2),
                '0.00',
            ));
            if ($grouped->isEmpty()) {
                throw ValidationException::withMessages(['allocations' => 'Informe ao menos uma movimentação.']);
            }
            $entryRemaining = $this->entryRemaining($entry, lock: true);
            $requested = $grouped->reduce(fn (string $sum, string $amount): string => bcadd($sum, $amount, 2), '0.00');
            if (bccomp($requested, '0', 2) <= 0 || bccomp($requested, $entryRemaining, 2) > 0) {
                throw ValidationException::withMessages(['allocations' => 'A soma informada supera a diferença pendente da linha bancária.']);
            }
            $movements = BankMovement::query()->whereKey($grouped->keys())->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            if ($movements->count() !== $grouped->count()) {
                throw ValidationException::withMessages(['allocations' => 'Uma movimentação informada não existe.']);
            }
            $expectedDirection = bccomp($entry->amount, '0', 2) >= 0 ? 'credit' : 'debit';
            foreach ($grouped as $movementId => $amount) {
                $movement = $movements->get($movementId);
                if ($movement->bank_account_id !== $entry->bank_account_id || $movement->direction !== $expectedDirection || $movement->status !== 'confirmed'
                    || bccomp($amount, $this->movementRemaining($movement, lock: true), 2) > 0) {
                    throw ValidationException::withMessages(['allocations' => 'Conta, direção, situação ou saldo da movimentação não permite esta alocação.']);
                }
                $existing = BankReconciliationAllocation::query()->where('bank_statement_entry_id', $entry->id)
                    ->where('bank_movement_id', $movement->id)->whereNull('reversed_at')->lockForUpdate()->first();
                if ($existing) {
                    $existing->update(['amount' => bcadd($existing->amount, $amount, 2)]);
                } else {
                    BankReconciliationAllocation::create([
                        'bank_statement_entry_id' => $entry->id, 'bank_movement_id' => $movement->id,
                        'amount' => $amount, 'created_by' => auth()->id(),
                    ]);
                }
            }
            $this->event($entry, 'allocated', metadata: ['amount' => $requested, 'movements' => $grouped->keys()->values()->all()]);
        }, 3);
    }

    public function complete(BankStatementEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $entry = BankStatementEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status === 'reconciled') {
                return;
            }
            if ($entry->status !== 'pending' || bccomp($this->entryRemaining($entry, lock: true), '0', 2) !== 0) {
                throw ValidationException::withMessages(['allocations' => 'A diferença deve ser zero antes de concluir a conciliação.']);
            }
            $entry->update(['status' => 'reconciled']);
            $this->event($entry, 'completed');
        }, 3);
    }

    public function reconcile(BankStatementEntry $entry, BankMovement $movement): void
    {
        if ($entry->status === 'reconciled') {
            $exists = $entry->activeAllocations()->where('bank_movement_id', $movement->id)->exists();
            if ($exists) {
                return;
            }
        }
        $amount = $this->entryRemaining($entry);
        if (bccomp($amount, $this->movementRemaining($movement), 2) !== 0) {
            throw ValidationException::withMessages(['movement' => 'Para conciliar diretamente, os saldos pendentes devem ser iguais.']);
        }
        $this->allocate($entry, [['movement_id' => $movement->id, 'amount' => $amount]]);
        $this->complete($entry);
    }

    public function undo(BankStatementEntry $entry, string $reason): void
    {
        if (! auth()->id() || trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Informe o responsável e a justificativa.']);
        }
        DB::transaction(function () use ($entry, $reason): void {
            $entry = BankStatementEntry::query()->lockForUpdate()->findOrFail($entry->id);
            $allocations = $entry->activeAllocations()->lockForUpdate()->get();
            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages(['reason' => 'Esta linha não possui conciliação ativa.']);
            }
            foreach ($allocations as $allocation) {
                $allocation->update(['reversed_at' => now(), 'reversed_by' => auth()->id(), 'reversal_reason' => trim($reason)]);
            }
            $entry->update(['status' => 'pending']);
            $this->event($entry, 'undone', $reason, ['allocations' => $allocations->modelKeys()]);
        }, 3);
    }

    public function archive(BankStatementEntry $entry, string $reason): void
    {
        if (trim($reason) === '') {
            throw ValidationException::withMessages(['reason' => 'Informe a justificativa do arquivamento.']);
        }
        DB::transaction(function () use ($entry, $reason): void {
            $entry = BankStatementEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== 'pending' || $entry->activeAllocations()->exists()) {
                throw ValidationException::withMessages(['reason' => 'Somente linhas pendentes sem alocações podem ser arquivadas.']);
            }
            $entry->update(['status' => 'archived', 'archived_at' => now(), 'archived_by' => auth()->id(), 'archive_reason' => trim($reason)]);
            $this->event($entry, 'archived', $reason);
        });
    }

    public function unarchive(BankStatementEntry $entry): void
    {
        DB::transaction(function () use ($entry): void {
            $entry = BankStatementEntry::query()->lockForUpdate()->findOrFail($entry->id);
            if ($entry->status !== 'archived') {
                throw ValidationException::withMessages(['entry' => 'Somente linhas arquivadas podem ser reabertas.']);
            }
            $entry->update(['status' => 'pending', 'archived_at' => null, 'archived_by' => null, 'archive_reason' => null]);
            $this->event($entry, 'unarchived');
        });
    }

    public function createAndReconcile(BankStatementEntry $entry, int $categoryId, ?string $description = null): BankMovement
    {
        return DB::transaction(function () use ($entry, $categoryId, $description): BankMovement {
            $entry = BankStatementEntry::query()->lockForUpdate()->findOrFail($entry->id);
            $amount = $this->entryRemaining($entry, lock: true);
            if ($entry->status !== 'pending' || bccomp($amount, '0', 2) <= 0) {
                throw ValidationException::withMessages(['entry' => 'A linha não possui diferença pendente.']);
            }
            $movement = BankMovement::create([
                'bank_account_id' => $entry->bank_account_id, 'category_id' => $categoryId,
                'occurred_at' => $entry->occurred_at, 'direction' => bccomp($entry->amount, '0', 2) >= 0 ? 'credit' : 'debit',
                'amount' => $amount, 'origin' => 'ofx', 'description' => $description ?: ($entry->memo ?: 'Lançamento OFX'),
                'reference' => $entry->document, 'status' => 'confirmed', 'created_by' => auth()->id(),
            ]);
            $this->reconcile($entry, $movement);

            return $movement;
        }, 3);
    }

    public function entryRemaining(BankStatementEntry $entry, bool $lock = false): string
    {
        $query = $entry->activeAllocations();
        if ($lock) {
            $query->lockForUpdate();
        }
        $allocated = $query->get()->reduce(fn (string $sum, $row): string => bcadd($sum, $row->amount, 2), '0.00');

        return bcsub(ltrim($entry->amount, '-'), $allocated, 2);
    }

    public function movementRemaining(BankMovement $movement, bool $lock = false): string
    {
        $query = $movement->activeReconciliationAllocations();
        if ($lock) {
            $query->lockForUpdate();
        }
        $allocated = $query->get()->reduce(fn (string $sum, $row): string => bcadd($sum, $row->amount, 2), '0.00');

        return bcsub($movement->amount, $allocated, 2);
    }

    public function dailyComparison(BankAccount $account, Carbon $start, Carbon $end): array
    {
        FinancialCashService::validatePeriod($start, $end);
        $entries = BankStatementEntry::where('bank_account_id', $account->id)->where('occurred_at', '<=', $end->copy()->endOfDay())
            ->whereNotNull('bank_balance')->orderBy('occurred_at')->orderBy('id')->get();
        $bankBalance = null;
        $rows = [];
        for ($day = $start->copy()->startOfDay(); $day->lte($end); $day->addDay()) {
            foreach ($entries->where('occurred_at', '<=', $day->copy()->endOfDay()) as $entry) {
                $bankBalance = $entry->bank_balance;
            }
            $system = $account->balanceAt($day->copy()->endOfDay());
            $rows[] = [
                'date' => $day->toDateString(), 'bank' => $bankBalance, 'system' => $system,
                'difference' => $bankBalance === null ? null : bcsub($bankBalance, $system, 2),
            ];
        }

        return $rows;
    }

    private function contains(?string $haystack, ?string $needle): bool
    {
        $normalize = fn (?string $text): string => mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $text)));
        $haystack = $normalize($haystack);
        $needle = $normalize($needle);

        return mb_strlen($needle) >= 3 && str_contains($haystack, $needle);
    }

    private function event(BankStatementEntry $entry, string $action, ?string $reason = null, ?array $metadata = null): void
    {
        $entry->events()->create(['action' => $action, 'reason' => $reason, 'metadata' => $metadata, 'created_by' => auth()->id()]);
    }
}
