<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>DRE</title>
    <style>
        @page { margin: 28px 34px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #718096; }
        table { width: 100%; border-collapse: separate; border-spacing: 0 2px; }
        td { padding: 5px 8px; }
        .document-header { margin-bottom: 16px; border-spacing: 0; }
        .document-header td { width: 50%; padding: 0; vertical-align: top; }
        .company { color: #475569; font-size: 12px; font-weight: bold; text-align: left; }
        .company-details { width: 245px; margin-top: 3px; color: #64748b; font-size: 8px; font-weight: normal; line-height: 1.45; }
        .company-contact { width: 200px; margin-top: 2px; border-spacing: 0; }
        .company-contact td { width: 50%; padding: 0; color: #64748b; font-size: 8px; font-weight: normal; }
        h1 { margin: 0; color: #2488df; font-size: 17px; text-align: right; }
        .period { margin-top: 5px; color: #718096; font-size: 10px; text-align: right; }
        .summary { margin-bottom: 18px; border-spacing: 5px 0; }
        .summary td { width: 25%; padding: 9px 6px; border: 1px solid #dbeafe; text-align: center; }
        .summary .entries { background: #eaf8fe; color: #1976d2; }
        .summary .expenses { background: #fff7ed; color: #9a4e12; }
        .summary .profit { background: #f0fdf4; color: #42804c; }
        .summary .profit.negative { background: #fef2f2; color: #b91c1c; }
        .summary .margin { background: #f8fafc; color: #475569; }
        .summary-label { font-size: 8px; font-weight: bold; }
        .summary-value { margin-top: 4px; font-size: 12px; font-weight: bold; }
        th { padding: 7px 8px; background: #d8f3ff; color: #1976d2; font-size: 9px; font-weight: bold; }
        .center { text-align: center; }
        .right { text-align: right; }
        .section-title { background: #d8f3ff; color: #1976d2; font-weight: bold; font-size: 11px; text-align: center; }
        .strong { font-weight: bold; }
        .group-name { background: #d8f3ff; color: #1976d2; font-weight: bold; text-align: center; }
        .expense-total { background: #ffe2d2; color: #7c6f68; font-weight: bold; font-size: 11px; }
        .result { background: #d8f8d5; color: #42794a; font-weight: bold; font-size: 12px; }
        .result.negative { background: #ffd9d9; color: #a33a3a; }
        .spacer td { height: 8px; padding: 0; }
        .footer { margin-top: 14px; color: #a0aec0; font-size: 8px; text-align: right; }
    </style>
</head>
<body>
    @php
        $totais = $report['totais'];
        $basePercentual = (float) $totais['receitas'];
        $percentual = fn ($valor) => $basePercentual > 0 ? ((float) $valor / $basePercentual) * 100 : 0;
        $fmt = fn ($valor) => 'R$ ' . number_format((float) $valor, 2, ',', '.');
        $pct = fn ($valor) => number_format($percentual($valor), 2, ',', '.') . '%';
        $renderNode = function ($node) use (&$renderNode, $fmt, $pct) {
            $isGroup = ! empty($node['children']);
            $class = $isGroup ? ' class="group-name"' : ' class="center"';
            $html = '<tr><td' . $class . '><strong>' . e($node['codigo']) . '</strong>&nbsp;&nbsp;' . e($node['descricao']) . '</td>';
            $html .= '<td class="right' . ($isGroup ? ' strong' : '') . '">' . $fmt($node['total']) . '</td>';
            $html .= '<td class="right' . ($isGroup ? ' strong' : '') . '">' . $pct($node['total']) . '</td></tr>';
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

    <table class="document-header">
        <tr>
            <td class="company">
                ALVES E NEVES LTDA
                <div class="company-details">INSTITUTO DE FORMAÇÃO HUMANA ALVES NEVES</div>
                <table class="company-contact">
                    <tr>
                        <td style="text-align:left;">58.955.315/0001-72</td>
                        <td style="text-align:right;">(63) 99996-3087</td>
                    </tr>
                </table>
                <div class="company-details">GURUPI-TO</div>
            </td>
            <td>
                <h1>DRE — Demonstração de Resultados</h1>
                <div class="period">Período: {{ $report['periodo']['inicio']->format('d/m/Y') }} a {{ $report['periodo']['fim']->format('d/m/Y') }}</div>
            </td>
        </tr>
    </table>

    <table class="summary">
        <tr>
            <td class="entries"><div class="summary-label">ENTRADAS</div><div class="summary-value">{{ $fmt($totais['receitas']) }}</div></td>
            <td class="expenses"><div class="summary-label">CUSTOS + DESPESAS</div><div class="summary-value">{{ $fmt($totais['custos'] + $totais['despesas']) }}</div></td>
            <td class="profit {{ $totais['lucro_liquido'] < 0 ? 'negative' : '' }}"><div class="summary-label">LUCRO LÍQUIDO</div><div class="summary-value">{{ $fmt($totais['lucro_liquido']) }}</div></td>
            <td class="margin"><div class="summary-label">MARGEM</div><div class="summary-value">{{ number_format($totais['margem_percentual'], 2, ',', '.') }}%</div></td>
        </tr>
    </table>

    <table>
        <thead><tr><th class="center">Conta</th><th class="right">Valor</th><th class="right">Participação nas receitas (%)</th></tr></thead>
        <tbody>
            <tr><td class="section-title">(+) RECEITAS</td><td class="right strong">{{ $fmt($totais['receitas']) }}</td><td class="right strong">{{ $pct($totais['receitas']) }}</td></tr>
            @if ($totais['entradas_mes'] > 0)
                <tr><td class="center strong">Receitas do mês</td><td class="right strong">{{ $fmt($totais['entradas_mes']) }}</td><td class="right strong">{{ $pct($totais['entradas_mes']) }}</td></tr>
                {!! $renderSection($report['entradas_mes']) !!}
            @endif
            @if ($totais['entradas_periodos_anteriores'] > 0)
                <tr><td class="center strong">Receitas referentes a meses anteriores</td><td class="right strong">{{ $fmt($totais['entradas_periodos_anteriores']) }}</td><td class="right strong">{{ $pct($totais['entradas_periodos_anteriores']) }}</td></tr>
                {!! $renderSection($report['entradas_periodos_anteriores']) !!}
            @endif

            @if ($totais['custos'] > 0)
                <tr class="spacer"><td colspan="3"></td></tr>
                <tr><td class="section-title">(−) CUSTOS</td><td class="right strong">{{ $fmt($totais['custos']) }}</td><td class="right strong">{{ $pct($totais['custos']) }}</td></tr>
                {!! $renderSection($report['custos']) !!}
                <tr><td class="center strong">(=) LUCRO BRUTO</td><td class="right strong">{{ $fmt($totais['lucro_bruto']) }}</td><td class="right strong">{{ $pct($totais['lucro_bruto']) }}</td></tr>
            @endif

            <tr class="spacer"><td colspan="3"></td></tr>
            <tr><td class="section-title">(−) DESPESAS</td><td class="right strong">{{ $fmt($totais['despesas']) }}</td><td class="right strong">{{ $pct($totais['despesas']) }}</td></tr>
            {!! $renderSection($report['despesas']) !!}

            <tr class="expense-total"><td class="center">Total de custos e despesas</td><td class="right">{{ $fmt($totais['custos'] + $totais['despesas']) }}</td><td class="right">{{ $pct($totais['custos'] + $totais['despesas']) }}</td></tr>
            <tr class="result {{ $totais['lucro_liquido'] < 0 ? 'negative' : '' }}"><td class="center">Resultado do período</td><td class="right">{{ $fmt($totais['lucro_liquido']) }}</td><td class="right">{{ number_format($totais['margem_percentual'], 2, ',', '.') }}%</td></tr>
        </tbody>
    </table>

    <div class="footer">Gerado em {{ now()->format('d/m/Y H:i') }}</div>
</body>
</html>
