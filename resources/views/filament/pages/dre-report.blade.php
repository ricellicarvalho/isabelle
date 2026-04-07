<x-filament-panels::page>
    <x-filament-panels::form wire:submit="generateReport">
        {{ $this->form }}
    </x-filament-panels::form>

    @if ($report)
        @php
            $totais = $report['totais'];
            $renderNode = function ($node, $depth = 0) use (&$renderNode) {
                $padding = $depth * 24;
                $html = '<tr class="border-b border-gray-100 dark:border-gray-700">';
                $html .= '<td class="py-2" style="padding-left: ' . $padding . 'px;">';
                $html .= '<span class="text-xs text-gray-500 mr-2">' . e($node['codigo']) . '</span>';
                $html .= e($node['descricao']);
                $html .= '</td>';
                $html .= '<td class="py-2 text-right tabular-nums">R$ ' . number_format($node['total'], 2, ',', '.') . '</td>';
                $html .= '</tr>';
                foreach ($node['children'] as $child) {
                    $html .= $renderNode($child, $depth + 1);
                }
                return $html;
            };
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                Período: {{ $report['periodo']['inicio']->format('d/m/Y') }} a {{ $report['periodo']['fim']->format('d/m/Y') }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-lg border border-success-200 bg-success-50 dark:bg-success-950/30 p-4">
                    <div class="text-xs text-success-700 dark:text-success-300 uppercase">Receitas</div>
                    <div class="text-2xl font-bold text-success-700 dark:text-success-300 tabular-nums">R$ {{ number_format($totais['receitas'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-warning-200 bg-warning-50 dark:bg-warning-950/30 p-4">
                    <div class="text-xs text-warning-700 dark:text-warning-300 uppercase">Custos + Despesas</div>
                    <div class="text-2xl font-bold text-warning-700 dark:text-warning-300 tabular-nums">R$ {{ number_format($totais['custos'] + $totais['despesas'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border {{ $totais['lucro_liquido'] >= 0 ? 'border-info-200 bg-info-50 dark:bg-info-950/30' : 'border-danger-200 bg-danger-50 dark:bg-danger-950/30' }} p-4">
                    <div class="text-xs uppercase {{ $totais['lucro_liquido'] >= 0 ? 'text-info-700 dark:text-info-300' : 'text-danger-700 dark:text-danger-300' }}">Lucro Líquido</div>
                    <div class="text-2xl font-bold tabular-nums {{ $totais['lucro_liquido'] >= 0 ? 'text-info-700 dark:text-info-300' : 'text-danger-700 dark:text-danger-300' }}">R$ {{ number_format($totais['lucro_liquido'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-gray-200 bg-gray-50 dark:bg-gray-900 p-4">
                    <div class="text-xs text-gray-600 dark:text-gray-400 uppercase">Margem</div>
                    <div class="text-2xl font-bold tabular-nums">{{ number_format($totais['margem_percentual'], 2, ',', '.') }}%</div>
                </div>
            </div>

            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                        <th class="text-left py-2 font-bold">Conta</th>
                        <th class="text-right py-2 font-bold">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="bg-success-50 dark:bg-success-950/20 font-bold">
                        <td class="py-2 px-2">(=) RECEITAS</td>
                        <td class="py-2 px-2 text-right tabular-nums text-success-700 dark:text-success-300">R$ {{ number_format($totais['receitas'], 2, ',', '.') }}</td>
                    </tr>
                    @foreach ($report['receitas'] as $node)
                        {!! $renderNode($node, 1) !!}
                    @endforeach

                    <tr class="bg-warning-50 dark:bg-warning-950/20 font-bold">
                        <td class="py-2 px-2">(–) CUSTOS</td>
                        <td class="py-2 px-2 text-right tabular-nums text-warning-700 dark:text-warning-300">R$ {{ number_format($totais['custos'], 2, ',', '.') }}</td>
                    </tr>
                    @foreach ($report['custos'] as $node)
                        {!! $renderNode($node, 1) !!}
                    @endforeach

                    <tr class="bg-info-50 dark:bg-info-950/20 font-bold border-t-2 border-gray-300">
                        <td class="py-3 px-2">(=) LUCRO BRUTO</td>
                        <td class="py-3 px-2 text-right tabular-nums">R$ {{ number_format($totais['lucro_bruto'], 2, ',', '.') }}</td>
                    </tr>

                    <tr class="bg-warning-50 dark:bg-warning-950/20 font-bold">
                        <td class="py-2 px-2">(–) DESPESAS</td>
                        <td class="py-2 px-2 text-right tabular-nums text-warning-700 dark:text-warning-300">R$ {{ number_format($totais['despesas'], 2, ',', '.') }}</td>
                    </tr>
                    @foreach ($report['despesas'] as $node)
                        {!! $renderNode($node, 1) !!}
                    @endforeach

                    <tr class="font-bold border-t-4 border-gray-400 {{ $totais['lucro_liquido'] >= 0 ? 'bg-info-100 dark:bg-info-950/40' : 'bg-danger-100 dark:bg-danger-950/40' }}">
                        <td class="py-3 px-2 text-base">(=) LUCRO/PREJUÍZO LÍQUIDO</td>
                        <td class="py-3 px-2 text-right tabular-nums text-base">R$ {{ number_format($totais['lucro_liquido'], 2, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </x-filament::section>
    @endif
</x-filament-panels::page>
