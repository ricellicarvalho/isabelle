<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Support\Carbon;

class CashFlowService
{
    /**
     * Gera o fluxo de caixa do período.
     *
     * @param  string  $regime  'caixa' (data_pagamento) ou 'competencia' (data_vencimento)
     * @return array{
     *     periodo: array{inicio: Carbon, fim: Carbon},
     *     regime: string,
     *     saldo_inicial: float,
     *     linhas: array,
     *     totais: array{entradas: float, saidas: float, saldo_final: float},
     * }
     */
    public static function generate(Carbon $inicio, Carbon $fim, string $regime = 'caixa', float $saldoInicial = 0): array
    {
        $inicio = $inicio->copy()->startOfDay();
        $fim = $fim->copy()->endOfDay();
        $dataField = $regime === 'caixa' ? 'data_pagamento' : 'data_vencimento';
        $statusFiltro = $regime === 'caixa' ? ['pago'] : ['pendente', 'vencido', 'pago'];

        $receivables = Receivable::query()
            ->with('category:id,descricao')
            ->whereIn('status', $statusFiltro)
            ->whereBetween($dataField, [$inicio, $fim])
            ->get()
            ->map(fn ($r) => [
                'data' => $r->{$dataField},
                'descricao' => $r->descricao,
                'categoria' => $r->category?->descricao ?? '—',
                'tipo' => 'entrada',
                'valor' => (float) ($regime === 'caixa' ? ($r->valor_pago ?? $r->valor) : $r->valor),
            ]);

        $payables = Payable::query()
            ->with('category:id,descricao')
            ->whereIn('status', $statusFiltro)
            ->whereBetween($dataField, [$inicio, $fim])
            ->get()
            ->map(fn ($p) => [
                'data' => $p->{$dataField},
                'descricao' => $p->descricao,
                'categoria' => $p->category?->descricao ?? '—',
                'tipo' => 'saida',
                'valor' => (float) ($regime === 'caixa' ? ($p->valor_pago ?? $p->valor) : $p->valor),
            ]);

        $linhas = $receivables->concat($payables)
            ->sortBy(fn ($l) => $l['data']?->timestamp ?? 0)
            ->values();

        $saldo = $saldoInicial;
        $totalEntradas = 0;
        $totalSaidas = 0;
        $linhasComSaldo = [];

        foreach ($linhas as $linha) {
            if ($linha['tipo'] === 'entrada') {
                $saldo += $linha['valor'];
                $totalEntradas += $linha['valor'];
            } else {
                $saldo -= $linha['valor'];
                $totalSaidas += $linha['valor'];
            }
            $linha['saldo_acumulado'] = $saldo;
            $linhasComSaldo[] = $linha;
        }

        return [
            'periodo' => ['inicio' => $inicio, 'fim' => $fim],
            'regime' => $regime,
            'saldo_inicial' => $saldoInicial,
            'linhas' => $linhasComSaldo,
            'totais' => [
                'entradas' => $totalEntradas,
                'saidas' => $totalSaidas,
                'saldo_final' => $saldo,
            ],
        ];
    }
}
