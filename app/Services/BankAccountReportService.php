<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankReconciliationAllocation;
use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class BankAccountReportService
{
    public static function generate(array $filters): array
    {
        $filters = Validator::make($filters, [
            'bank_account_id' => ['required', 'integer', 'exists:bank_accounts,id'],
            'data_inicio' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.BankMovementService::CONTROL_START],
            'data_fim' => ['required', 'date_format:Y-m-d', 'after_or_equal:data_inicio'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'reconciliation' => ['nullable', 'in:pending,reconciled'],
            'search' => ['nullable', 'string', 'max:255'],
        ])->validate();
        $account = BankAccount::findOrFail($filters['bank_account_id']);
        $start = Carbon::parse($filters['data_inicio'])->startOfDay();
        $end = Carbon::parse($filters['data_fim'])->endOfDay();
        if (! $account->opening_balance_date || $start->lte($account->opening_balance_date)) {
            throw ValidationException::withMessages(['data_inicio' => 'Defina o saldo inicial da conta e selecione um período posterior à sua data.']);
        }
        $opening = $account->balanceAt($start->copy()->subSecond());
        $balance = $opening;
        $credits = $debits = $selectedCredits = $selectedDebits = '0.00';
        $rows = [];
        $movements = $account->movements()->with('category')->where('status', 'confirmed')
            ->whereBetween('occurred_at', [$start, $end])->orderBy('occurred_at')->orderBy('id')->get();
        $allocations = BankReconciliationAllocation::whereIn('bank_movement_id', $movements->modelKeys())->whereNull('reversed_at')
            ->get()->groupBy('bank_movement_id');
        foreach ($movements as $movement) {
            $credit = $movement->direction === 'credit' ? $movement->amount : '0.00';
            $debit = $movement->direction === 'debit' ? $movement->amount : '0.00';
            $credits = bcadd($credits, $credit, 2);
            $debits = bcadd($debits, $debit, 2);
            $balance = bcsub(bcadd($balance, $credit, 2), $debit, 2);
            $allocated = '0.00';
            foreach ($allocations->get($movement->id, collect()) as $allocation) {
                $allocated = bcadd($allocated, $allocation->amount, 2);
            }
            $reconciled = bccomp($allocated, $movement->amount, 2) === 0;
            if (filled($filters['category_id'] ?? null) && $movement->category_id !== (int) $filters['category_id']) {
                continue;
            }
            if (($filters['reconciliation'] ?? null) === 'reconciled' && ! $reconciled) {
                continue;
            }
            if (($filters['reconciliation'] ?? null) === 'pending' && $reconciled) {
                continue;
            }
            $search = trim($filters['search'] ?? '');
            if ($search !== '' && ! str_contains(mb_strtolower($movement->counterparty.' '.$movement->reference), mb_strtolower($search))) {
                continue;
            }
            $selectedCredits = bcadd($selectedCredits, $credit, 2);
            $selectedDebits = bcadd($selectedDebits, $debit, 2);
            $rows[] = [
                'id' => $movement->id, 'data' => $movement->occurred_at->format('d/m/Y H:i'),
                'reference' => $movement->reference, 'description' => $movement->description,
                'counterparty' => $movement->counterparty, 'category' => $movement->category?->descricao ?? ($movement->origin === 'transfer' ? 'Transferência' : 'Sem categoria'),
                'reconciliation' => $reconciled ? 'Conciliado' : 'Pendente', 'credit' => $credit, 'debit' => $debit, 'balance' => $balance,
            ];
        }

        return [
            'company' => 'Instituto Alves Neves',
            'account' => ['id' => $account->id, 'name' => $account->display_name, 'bank' => $account->banco_nome, 'agency' => $account->agencia, 'number' => $account->conta.($account->conta_dv ? '-'.$account->conta_dv : ''), 'credit_limit' => $account->credit_limit],
            'filters' => $filters + ['category' => filled($filters['category_id'] ?? null) ? Category::find($filters['category_id'])?->descricao : null],
            'period' => ['start' => $start->format('d/m/Y'), 'end' => $end->format('d/m/Y')],
            'rows' => $rows, 'opening' => $opening, 'credits' => $credits, 'debits' => $debits,
            'net' => bcsub($credits, $debits, 2), 'closing' => $balance, 'available' => bcadd($balance, $account->credit_limit, 2),
            'selected_credits' => $selectedCredits, 'selected_debits' => $selectedDebits,
            'filtered' => filled($filters['category_id'] ?? null) || filled($filters['reconciliation'] ?? null) || filled($filters['search'] ?? null),
            'unclassified' => FinancialCashService::unclassifiedCount($start, $end, $account->id),
        ];
    }
}
