<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DreService
{
    /**
     * Gera o DRE (Demonstração de Resultados) para o período informado.
     *
     * Regime de caixa: títulos legados antes do corte e movimentos confirmados
     * após o corte, classificados por categoria e sem transferências internas.
     *
     * @return array{
     *     periodo: array{inicio: Carbon, fim: Carbon},
     *     receitas: array,
     *     custos: array,
     *     despesas: array,
     *     totais: array,
     * }
     */
    public static function generate(Carbon $inicio, Carbon $fim, ?int $accountId = null): array
    {
        $inicio = $inicio->copy()->startOfDay();
        $fim = $fim->copy()->endOfDay();

        FinancialCashService::validatePeriod($inicio, $fim);
        $receitasMap = $receitasDoMesMap = $receitasAnterioresMap = $pagosMap = [];
        foreach (FinancialCashService::rows($inicio, $fim, $accountId) as $row) {
            if (in_array($row['origin'], ['transfer', 'opening_balance'], true)) {
                continue;
            }
            $categoryId = $row['category_id'];
            if ($row['categoria_tipo'] === 'receita') {
                $amount = $row['tipo'] === 'entrada' ? $row['valor'] : bcsub('0', $row['valor'], 2);
                $receitasMap[$categoryId] = bcadd($receitasMap[$categoryId] ?? '0', $amount, 2);
                $previous = $row['vencimento'] && $row['vencimento']->copy()->startOfMonth()->lt($row['data']->copy()->startOfMonth());
                if ($previous) {
                    $receitasAnterioresMap[$categoryId] = bcadd($receitasAnterioresMap[$categoryId] ?? '0', $amount, 2);
                } else {
                    $receitasDoMesMap[$categoryId] = bcadd($receitasDoMesMap[$categoryId] ?? '0', $amount, 2);
                }
            } elseif (in_array($row['categoria_tipo'], ['custo', 'despesa'], true)) {
                $amount = $row['tipo'] === 'saida' ? $row['valor'] : bcsub('0', $row['valor'], 2);
                $pagosMap[$categoryId] = bcadd($pagosMap[$categoryId] ?? '0', $amount, 2);
            }
        }

        $categorias = Category::query()->orderBy('order')->get();

        $receitas = self::buildTree($categorias, 'receita', $receitasMap);
        $receitasDoMes = self::buildTree($categorias, 'receita', $receitasDoMesMap);
        $receitasAnteriores = self::buildTree($categorias, 'receita', $receitasAnterioresMap);
        $custos = self::buildTree($categorias, 'custo', $pagosMap);
        $despesas = self::buildTree($categorias, 'despesa', $pagosMap);

        $totalReceitas = self::sumNodes($receitas);
        $totalReceitasDoMes = self::sumNodes($receitasDoMes);
        $totalReceitasAnteriores = self::sumNodes($receitasAnteriores);
        $totalCustos = self::sumNodes($custos);
        $totalDespesas = self::sumNodes($despesas);

        $lucroBruto = bcsub($totalReceitas, $totalCustos, 2);
        $lucroLiquido = bcsub($lucroBruto, $totalDespesas, 2);
        $margem = bccomp($totalReceitas, '0', 2) > 0 ? bcmul(bcdiv($lucroLiquido, $totalReceitas, 6), '100', 2) : '0.00';

        return [
            'unclassified' => FinancialCashService::unclassifiedCount($inicio, $fim, $accountId),
            'bank_account_id' => $accountId,
            'periodo' => ['inicio' => $inicio, 'fim' => $fim],
            'receitas' => $receitas,
            'entradas_mes' => $receitasDoMes,
            'entradas_periodos_anteriores' => $receitasAnteriores,
            'custos' => $custos,
            'despesas' => $despesas,
            'totais' => [
                'receitas' => $totalReceitas,
                'entradas_mes' => $totalReceitasDoMes,
                'entradas_periodos_anteriores' => $totalReceitasAnteriores,
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
            $valorProprio = $valoresMap[$cat->id] ?? '0.00';
            $valorFilhos = self::sumNodes($children);
            $total = bcadd($valorProprio, $valorFilhos, 2);

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

    protected static function sumNodes(array $nodes): string
    {
        $total = '0.00';
        foreach ($nodes as $node) {
            $total = bcadd($total, $node['total'], 2);
        }

        return $total;
    }
}
