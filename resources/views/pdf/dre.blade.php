<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>DRE</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; }
        h1 { font-size: 18px; margin: 0 0 4px 0; }
        .periodo { font-size: 11px; color: #666; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 2px solid #333; padding: 6px 4px; }
        td { padding: 4px; border-bottom: 1px solid #eee; }
        td.right { text-align: right; }
        tr.section td { background: #f0f0f0; font-weight: bold; }
        tr.bruto td { background: #e6f0ff; font-weight: bold; border-top: 2px solid #999; }
        tr.liquido td { background: #d4edda; font-weight: bold; font-size: 13px; border-top: 3px solid #555; }
        tr.liquido.negative td { background: #f8d7da; }
        .codigo { color: #888; font-size: 9px; margin-right: 4px; }
        .resumo { margin-bottom: 12px; }
        .resumo td { border: 1px solid #ddd; padding: 6px; width: 25%; }
        .resumo .label { font-size: 9px; color: #666; text-transform: uppercase; }
        .resumo .value { font-size: 14px; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $totais = $report['totais'];
        $renderNode = function ($node, $depth = 0) use (&$renderNode) {
            $padding = $depth * 16;
            $html = '<tr><td style="padding-left: ' . $padding . 'px;"><span class="codigo">' . e($node['codigo']) . '</span>' . e($node['descricao']) . '</td>';
            $html .= '<td class="right">R$ ' . number_format($node['total'], 2, ',', '.') . '</td></tr>';
            foreach ($node['children'] as $child) {
                $html .= $renderNode($child, $depth + 1);
            }
            return $html;
        };
    @endphp

    <h1>DRE — Demonstração de Resultados</h1>
    <div class="periodo">
        Período: {{ $report['periodo']['inicio']->format('d/m/Y') }} a {{ $report['periodo']['fim']->format('d/m/Y') }}
    </div>

    <table class="resumo">
        <tr>
            <td><div class="label">Receitas</div><div class="value">R$ {{ number_format($totais['receitas'], 2, ',', '.') }}</div></td>
            <td><div class="label">Custos+Despesas</div><div class="value">R$ {{ number_format($totais['custos'] + $totais['despesas'], 2, ',', '.') }}</div></td>
            <td><div class="label">Lucro Líquido</div><div class="value">R$ {{ number_format($totais['lucro_liquido'], 2, ',', '.') }}</div></td>
            <td><div class="label">Margem</div><div class="value">{{ number_format($totais['margem_percentual'], 2, ',', '.') }}%</div></td>
        </tr>
    </table>

    <table>
        <thead>
            <tr><th>Conta</th><th style="text-align: right;">Valor</th></tr>
        </thead>
        <tbody>
            <tr class="section"><td>(=) RECEITAS</td><td class="right">R$ {{ number_format($totais['receitas'], 2, ',', '.') }}</td></tr>
            @foreach ($report['receitas'] as $node) {!! $renderNode($node, 1) !!} @endforeach

            <tr class="section"><td>(–) CUSTOS</td><td class="right">R$ {{ number_format($totais['custos'], 2, ',', '.') }}</td></tr>
            @foreach ($report['custos'] as $node) {!! $renderNode($node, 1) !!} @endforeach

            <tr class="bruto"><td>(=) LUCRO BRUTO</td><td class="right">R$ {{ number_format($totais['lucro_bruto'], 2, ',', '.') }}</td></tr>

            <tr class="section"><td>(–) DESPESAS</td><td class="right">R$ {{ number_format($totais['despesas'], 2, ',', '.') }}</td></tr>
            @foreach ($report['despesas'] as $node) {!! $renderNode($node, 1) !!} @endforeach

            <tr class="liquido {{ $totais['lucro_liquido'] < 0 ? 'negative' : '' }}">
                <td>(=) LUCRO/PREJUÍZO LÍQUIDO</td>
                <td class="right">R$ {{ number_format($totais['lucro_liquido'], 2, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <p style="margin-top: 16px; font-size: 9px; color: #888;">Gerado em {{ now()->format('d/m/Y H:i') }}</p>
</body>
</html>
