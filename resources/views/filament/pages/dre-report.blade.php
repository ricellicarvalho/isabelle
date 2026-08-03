<x-filament-panels::page>
    <form wire:submit.prevent="generateReport">
        {{ $this->form }}
    </form>

    @if ($report)
        @php
            $totais = $report['totais'];
            $basePercentual = (float) $totais['receitas'];
            $percentual = fn ($valor) => $basePercentual > 0 ? ((float) $valor / $basePercentual) * 100 : 0;
            $fmt = fn ($valor) => 'R$ ' . number_format((float) $valor, 2, ',', '.');
            $pct = fn ($valor) => number_format($percentual($valor), 2, ',', '.') . '%';
            $renderNode = function ($node) use (&$renderNode, $fmt, $pct) {
                $isGroup = ! empty($node['children']);
                $background = $isGroup ? 'background:#d8f3ff;color:#1976d2;font-weight:700;' : '';
                $html = '<tr>';
                $html .= '<td style="padding:8px 12px;text-align:center;' . $background . '"><strong style="margin-right:8px;">' . e($node['codigo']) . '</strong>' . e($node['descricao']) . '</td>';
                $html .= '<td style="padding:8px 12px;text-align:right;font-variant-numeric:tabular-nums;' . ($isGroup ? 'font-weight:700;' : '') . '">' . $fmt($node['total']) . '</td>';
                $html .= '<td style="padding:8px 12px;text-align:right;font-variant-numeric:tabular-nums;' . ($isGroup ? 'font-weight:700;' : '') . '">' . $pct($node['total']) . '</td></tr>';
                foreach ($node['children'] as $child) {
                    $html .= $renderNode($child);
                }
                return $html;
            };
            $renderSection = function ($nodes) use ($renderNode) {
                $html = '';
                foreach ($nodes as $node) {
                    if (! str_contains((string) $node['codigo'], '.') && ! empty($node['children'])) {
                        foreach ($node['children'] as $child) {
                            $html .= $renderNode($child);
                        }
                    } else {
                        $html .= $renderNode($node);
                    }
                }
                return $html;
            };
        @endphp

        <x-filament::section>
            <div style="text-align:center;margin-bottom:20px;">
                <h2 style="margin:0;color:#2488df;font-size:24px;font-weight:800;">DRE — Demonstração de Resultados</h2>
                <p style="margin:6px 0 0;color:#64748b;font-size:14px;">Período: {{ $report['periodo']['inicio']->format('d/m/Y') }} a {{ $report['periodo']['fim']->format('d/m/Y') }}</p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-bottom:24px;">
                <div style="border:1px solid #b9e9fb;background:#eaf8fe;border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:11px;font-weight:800;color:#1976d2;letter-spacing:.04em;">ENTRADAS</div>
                    <div style="margin-top:5px;font-size:20px;font-weight:800;color:#1976d2;">{{ $fmt($totais['receitas']) }}</div>
                </div>
                <div style="border:1px solid #fed7aa;background:#fff7ed;border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:11px;font-weight:800;color:#c56a18;letter-spacing:.04em;">CUSTOS + DESPESAS</div>
                    <div style="margin-top:5px;font-size:20px;font-weight:800;color:#9a4e12;">{{ $fmt($totais['custos'] + $totais['despesas']) }}</div>
                </div>
                <div style="border:1px solid {{ $totais['lucro_liquido'] >= 0 ? '#bbf7d0' : '#fecaca' }};background:{{ $totais['lucro_liquido'] >= 0 ? '#f0fdf4' : '#fef2f2' }};border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:11px;font-weight:800;color:{{ $totais['lucro_liquido'] >= 0 ? '#42804c' : '#b91c1c' }};letter-spacing:.04em;">LUCRO LÍQUIDO</div>
                    <div style="margin-top:5px;font-size:20px;font-weight:800;color:{{ $totais['lucro_liquido'] >= 0 ? '#42804c' : '#b91c1c' }};">{{ $fmt($totais['lucro_liquido']) }}</div>
                </div>
                <div style="border:1px solid #dbe4ef;background:#f8fafc;border-radius:10px;padding:14px;text-align:center;">
                    <div style="font-size:11px;font-weight:800;color:#64748b;letter-spacing:.04em;">MARGEM</div>
                    <div style="margin-top:5px;font-size:20px;font-weight:800;color:#475569;">{{ number_format($totais['margem_percentual'], 2, ',', '.') }}%</div>
                </div>
            </div>

            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:separate;border-spacing:0 3px;font-size:14px;color:#64748b;">
                    <thead><tr style="background:#d8f3ff;color:#1976d2;">
                        <th style="padding:10px 12px;text-align:center;font-weight:800;">Conta</th>
                        <th style="padding:10px 12px;text-align:right;font-weight:800;">Valor</th>
                        <th title="Valor da linha dividido pelo total de receitas do período" style="padding:10px 12px;text-align:right;font-weight:800;">Participação nas receitas (%)</th>
                    </tr></thead>
                    <tbody>
                        <tr style="color:#1976d2;font-weight:800;"><td style="padding:12px;text-align:center;background:#d8f3ff;">(+) RECEITAS</td><td style="padding:12px;text-align:right;">{{ $fmt($totais['receitas']) }}</td><td style="padding:12px;text-align:right;">{{ $pct($totais['receitas']) }}</td></tr>
                        @if ($totais['entradas_mes'] > 0)
                            <tr style="font-weight:700;"><td style="padding:8px 12px;text-align:center;">Receitas do mês</td><td style="padding:8px 12px;text-align:right;">{{ $fmt($totais['entradas_mes']) }}</td><td style="padding:8px 12px;text-align:right;">{{ $pct($totais['entradas_mes']) }}</td></tr>
                            {!! $renderSection($report['entradas_mes']) !!}
                        @endif
                        @if ($totais['entradas_periodos_anteriores'] > 0)
                            <tr style="font-weight:700;"><td style="padding:8px 12px;text-align:center;">Receitas referentes a meses anteriores</td><td style="padding:8px 12px;text-align:right;">{{ $fmt($totais['entradas_periodos_anteriores']) }}</td><td style="padding:8px 12px;text-align:right;">{{ $pct($totais['entradas_periodos_anteriores']) }}</td></tr>
                            {!! $renderSection($report['entradas_periodos_anteriores']) !!}
                        @endif

                        <tr><td colspan="3" style="height:12px;"></td></tr>
                        @if ($totais['custos'] > 0)
                            <tr style="color:#1976d2;font-weight:800;"><td style="padding:12px;text-align:center;background:#d8f3ff;">(−) CUSTOS</td><td style="padding:12px;text-align:right;">{{ $fmt($totais['custos']) }}</td><td style="padding:12px;text-align:right;">{{ $pct($totais['custos']) }}</td></tr>
                            {!! $renderSection($report['custos']) !!}
                            <tr style="font-weight:800;"><td style="padding:10px 12px;text-align:center;">(=) LUCRO BRUTO</td><td style="padding:10px 12px;text-align:right;">{{ $fmt($totais['lucro_bruto']) }}</td><td style="padding:10px 12px;text-align:right;">{{ $pct($totais['lucro_bruto']) }}</td></tr>
                        @endif

                        <tr><td colspan="3" style="height:12px;"></td></tr>
                        <tr style="color:#1976d2;font-weight:800;"><td style="padding:12px;text-align:center;background:#d8f3ff;">(−) DESPESAS</td><td style="padding:12px;text-align:right;">{{ $fmt($totais['despesas']) }}</td><td style="padding:12px;text-align:right;">{{ $pct($totais['despesas']) }}</td></tr>
                        {!! $renderSection($report['despesas']) !!}

                        <tr style="background:#ffe2d2;color:#7c6f68;font-weight:800;"><td style="padding:11px 12px;text-align:center;">Total de custos e despesas</td><td style="padding:11px 12px;text-align:right;">{{ $fmt($totais['custos'] + $totais['despesas']) }}</td><td style="padding:11px 12px;text-align:right;">{{ $pct($totais['custos'] + $totais['despesas']) }}</td></tr>
                        <tr style="background:#d8f8d5;color:#42794a;font-weight:800;font-size:16px;"><td style="padding:12px;text-align:center;">Resultado do período</td><td style="padding:12px;text-align:right;">{{ $fmt($totais['lucro_liquido']) }}</td><td style="padding:12px;text-align:right;">{{ number_format($totais['margem_percentual'], 2, ',', '.') }}%</td></tr>
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
