<?php

namespace App\Filament\Widgets;

use App\Services\DreService;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;

class ExpenseCompositionChart extends ChartWidget
{
    use InteractsWithPageFilters;

    protected ?string $heading = 'Composição dos custos e despesas';

    protected ?string $description = 'Mostra onde os recursos foram consumidos no período, considerando a data de pagamento.';

    protected int|string|array $columnSpan = 'full';

    protected ?string $maxHeight = '340px';

    protected function getData(): array
    {
        $inicio = Carbon::parse($this->pageFilters['data_inicio'] ?? now()->startOfMonth())->startOfDay();
        $fim = Carbon::parse($this->pageFilters['data_fim'] ?? now()->endOfMonth())->endOfDay();
        $dre = DreService::generate($inicio, $fim);
        $items = [];

        $this->collectOwnValues([...$dre['custos'], ...$dre['despesas']], $items);
        uasort($items, fn (float $a, float $b): int => $b <=> $a);

        $colors = [
            '#2563eb', '#7c3aed', '#db2777', '#ea580c', '#ca8a04',
            '#16a34a', '#0891b2', '#4f46e5', '#9333ea', '#e11d48',
        ];
        $backgroundColors = [];
        for ($index = 0; $index < count($items); $index++) {
            $backgroundColors[] = $colors[$index % count($colors)];
        }

        return [
            'datasets' => [[
                'label' => 'Valor pago',
                'data' => array_values($items),
                'backgroundColor' => $backgroundColors,
                'borderWidth' => 1,
            ]],
            'labels' => array_keys($items),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    public function rendering(): void
    {
        $this->cachedData = null;

        parent::rendering();
    }

    /**
     * @param  array<string, float>  $items
     */
    protected function collectOwnValues(array $nodes, array &$items): void
    {
        foreach ($nodes as $node) {
            if ((float) $node['valor_proprio'] > 0) {
                $label = trim($node['codigo'].' — '.$node['descricao']);
                $items[$label] = ($items[$label] ?? 0) + (float) $node['valor_proprio'];
            }

            $this->collectOwnValues($node['children'], $items);
        }
    }
}
