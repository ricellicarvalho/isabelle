<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class CashFlowService
{
    public static function generate(Carbon $inicio, Carbon $fim, string $regime = 'caixa', string $saldoInicial = '0.00', ?int $accountId = null): array
    {
        FinancialCashService::validatePeriod($inicio, $fim);
        if (! in_array($regime, ['caixa', 'competencia'], true)) {
            throw ValidationException::withMessages(['regime' => 'Regime inválido.']);
        }
        $inicio = $inicio->copy()->startOfDay();
        $fim = $fim->copy()->endOfDay();
        $cutoff = Carbon::parse(BankMovementService::CONTROL_START);
        $bridge = '0.00';
        if ($regime === 'caixa') {
            $rows = FinancialCashService::rows($inicio, $fim, $accountId)
                // Internal transfers only redistribute consolidated cash.
                ->reject(fn ($row) => ! $accountId && $row['origin'] === 'transfer')->values();
            if ($inicio->gte($cutoff)) {
                $saldoInicial = FinancialCashService::balanceBefore($inicio, $accountId);
            }
            foreach (\App\Models\BankAccount::query()->when($accountId, fn ($q) => $q->whereKey($accountId))->whereNotNull('opening_balance_date')->get() as $account) {
                $openingDate = $account->opening_balance_date->copy()->endOfDay();
                if ($openingDate->gt($cutoff) && $openingDate->gte($inicio) && $openingDate->lte($fim)) {
                    $rows->push(['id' => '0opening:'.$account->id, 'data' => $openingDate, 'descricao' => 'Saldo de abertura — '.$account->display_name, 'categoria' => 'Saldo inicial da conta', 'tipo' => 'abertura', 'valor' => $account->opening_balance]);
                }
            }
            $rows = $rows->sortBy([['data', 'asc'], ['id', 'asc']])->values();
        } else {
            $rows = collect();
            foreach ([Receivable::class => 'entrada', Payable::class => 'saida'] as $model => $type) {
                $titles = FinancialCashService::titleAccount($model::with('category')->whereIn('status', ['pendente', 'vencido', 'pago'])
                    ->whereBetween('data_vencimento', [$inicio, $fim]), $accountId)->get();
                foreach ($titles as $title) {
                    $rows->push(['data' => $title->data_vencimento, 'descricao' => $title->descricao, 'categoria' => $title->category?->descricao ?? '—', 'tipo' => $type, 'valor' => $title->valor]);
                }
            }
            $rows = $rows->sortBy('data')->values();
        }
        if (! preg_match('/^-?\d{1,12}(\.\d{1,2})?$/D', $saldoInicial)) {
            throw ValidationException::withMessages(['saldo_inicial' => 'Saldo inicial inválido.']);
        }
        $saldoInicial = bcadd($saldoInicial, '0', 2);
        $balance = $saldoInicial;
        $credits = $debits = '0.00';
        $lines = [];
        $crossesCutoff = $regime === 'caixa' && $inicio->lt($cutoff) && $fim->gte($cutoff);
        $insertOpening = function () use (&$lines, &$balance, &$bridge, $cutoff, $accountId): void {
            $opening = FinancialCashService::balanceBefore($cutoff, $accountId);
            $bridge = bcsub($opening, $balance, 2);
            $balance = $opening;
            $lines[] = ['data' => $cutoff, 'descricao' => 'Abertura do controle bancário em 01/08/2026', 'categoria' => 'Saldo inicial das contas', 'tipo' => 'abertura', 'valor' => $bridge, 'saldo_acumulado' => $balance];
        };
        foreach ($rows as $row) {
            if ($crossesCutoff && $row['data']->gte($cutoff)) {
                $insertOpening();
                $crossesCutoff = false;
            }
            if ($row['tipo'] === 'abertura') {
                $balance = bcadd($balance, $row['valor'], 2);
                $bridge = bcadd($bridge, $row['valor'], 2);
            } elseif ($row['tipo'] === 'entrada') {
                $balance = bcadd($balance, $row['valor'], 2);
                $credits = bcadd($credits, $row['valor'], 2);
            } else {
                $balance = bcsub($balance, $row['valor'], 2);
                $debits = bcadd($debits, $row['valor'], 2);
            }
            $row['saldo_acumulado'] = $balance;
            $lines[] = $row;
        }
        if ($crossesCutoff) {
            $insertOpening();
        }

        return [
            'periodo' => ['inicio' => $inicio, 'fim' => $fim], 'regime' => $regime, 'bank_account_id' => $accountId,
            'saldo_inicial' => $saldoInicial, 'ajuste_abertura' => $bridge, 'linhas' => $lines,
            'totais' => ['entradas' => $credits, 'saidas' => $debits, 'saldo_final' => $balance],
            'unclassified' => $regime === 'caixa' ? FinancialCashService::unclassifiedCount($inicio, $fim, $accountId) : 0,
        ];
    }
}
