<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Payable;
use App\Models\Receivable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DreService
{
    /**
     * Gera o DRE (Demonstração de Resultados) para o período informado.
     *
     * Regime: caixa — considera apenas movimentações com status 'pago'
     * dentro do intervalo de data_pagamento.
     *
     * @return array{
     *     periodo: array{inicio: Carbon, fim: Carbon},
     *     receitas: array,
     *     custos: array,
     *     despesas: array,
     *     totais: array,
     * }
     */
    public static function generate(Carbon $inicio, Carbon $fim): array
    {
        $inicio = $inicio->copy()->startOfDay();
        $fim = $fim->copy()->endOfDay();

        $receivablesPagos = Receivable::query()
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->get(['category_id', 'valor_pago', 'valor']);

        $payablesPagos = Payable::query()
            ->where('status', 'pago')
            ->whereBetween('data_pagamento', [$inicio, $fim])
            ->get(['category_id', 'valor_pago', 'valor']);

        $somaPorCategoria = function (Collection $items): array {
            $map = [];
            foreach ($items as $item) {
                $valor = (float) ($item->valor_pago ?? $item->valor);
                $map[$item->category_id] = ($map[$item->category_id] ?? 0) + $valor;
            }

            return $map;
        };

        $receitasMap = $somaPorCategoria($receivablesPagos);
        $pagosMap = $somaPorCategoria($payablesPagos);

        $categorias = Category::query()->orderBy('order')->get();

        $receitas = self::buildTree($categorias, 'receita', $receitasMap);
        $custos = self::buildTree($categorias, 'custo', $pagosMap);
        $despesas = self::buildTree($categorias, 'despesa', $pagosMap);

        $totalReceitas = self::sumNodes($receitas);
        $totalCustos = self::sumNodes($custos);
        $totalDespesas = self::sumNodes($despesas);

        $lucroBruto = $totalReceitas - $totalCustos;
        $lucroLiquido = $lucroBruto - $totalDespesas;
        $margem = $totalReceitas > 0 ? ($lucroLiquido / $totalReceitas) * 100 : 0;

        return [
            'periodo' => ['inicio' => $inicio, 'fim' => $fim],
            'receitas' => $receitas,
            'custos' => $custos,
            'despesas' => $despesas,
            'totais' => [
                'receitas' => $totalReceitas,
                'custos' => $totalCustos,
                'lucro_bruto' => $lucroBruto,
                'despesas' => $totalDespesas,
                'lucro_liquido' => $lucroLiquido,
                'margem_percentual' => $margem,
            ],
        ];
    }

    /**
     * Constrói árvore hierárquica de categorias do tipo informado, com totais
     * acumulados de pais somando os filhos.
     */
    protected static function buildTree(Collection $categorias, string $tipo, array $valoresMap, ?int $parentId = null): array
    {
        $nodes = [];

        foreach ($categorias->where('parent_id', $parentId)->where('tipo', $tipo) as $cat) {
            $children = self::buildTree($categorias, $tipo, $valoresMap, $cat->id);
            $valorProprio = (float) ($valoresMap[$cat->id] ?? 0);
            $valorFilhos = array_sum(array_column($children, 'total'));
            $total = $valorProprio + $valorFilhos;

            if ($total == 0 && empty($children)) {
                continue;
            }

            $nodes[] = [
                'id' => $cat->id,
                'codigo' => $cat->codigo,
                'descricao' => $cat->descricao,
                'valor_proprio' => $valorProprio,
                'total' => $total,
                'children' => $children,
            ];
        }

        return $nodes;
    }

    protected static function sumNodes(array $nodes): float
    {
        return array_sum(array_column($nodes, 'total'));
    }
}
