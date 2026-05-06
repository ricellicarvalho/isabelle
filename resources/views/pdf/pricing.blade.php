<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Precificação NR-1 — {{ $pricing->nome }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 20px; }
        h1 { font-size: 16px; margin: 0 0 2px 0; color: #1e084a; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 18px; }

        .section { margin-bottom: 16px; }
        .section-title { font-size: 12px; font-weight: bold; color: #1e084a; border-bottom: 2px solid #1e084a; padding-bottom: 3px; margin-bottom: 8px; }

        /* Identificação */
        table.info { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        table.info td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        table.info td.lbl { font-weight: bold; width: 40%; color: #444; }

        /* Calculadora */
        table.calc { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        table.calc th { background: #1e084a; color: #fff; padding: 5px 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: .5px; }
        table.calc th.right { text-align: right; }
        table.calc td { padding: 5px 8px; border-bottom: 1px solid #eee; font-size: 10px; }
        table.calc td.right { text-align: right; }
        table.calc tr.despesas td { background: #fafafa; color: #555; }
        table.calc tr.total td { background: #ede9fe; font-weight: bold; border-top: 2px solid #1e084a; }
        table.calc tr.total td.right { text-align: right; }

        /* Resumo */
        table.resumo { width: 100%; border-collapse: collapse; }
        table.resumo td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        table.resumo td.lbl { font-weight: bold; color: #444; width: 55%; }
        table.resumo td.val { text-align: right; }
        table.resumo tr.c-imposto td { background: #1e084a; color: #fff; font-weight: bold; font-size: 13px; }
        table.resumo tr.lucro td { background: #d4edda; color: #155724; font-weight: bold; }
        table.resumo tr.parcela td { background: #fff3cd; color: #856404; font-weight: bold; }

        .params { margin-bottom: 10px; font-size: 10px; color: #555; }
        .params span { margin-right: 20px; }
        .footer { margin-top: 28px; font-size: 8px; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>Precificação NR-1</h1>
    <div class="subtitle">Gerado em {{ now()->format('d/m/Y H:i') }}</div>

    {{-- Identificação --}}
    <div class="section">
        <div class="section-title">Identificação</div>
        <table class="info">
            <tr><td class="lbl">Serviço / Ação</td><td>{{ $pricing->nome }}</td></tr>
            <tr><td class="lbl">Categoria</td><td>{{ $pricing->category?->descricao ?? '—' }}</td></tr>
            @if($pricing->descricao)
            <tr><td class="lbl">Descrição</td><td>{{ $pricing->descricao }}</td></tr>
            @endif
            @if($pricing->deslocamento)
            <tr><td class="lbl">Deslocamento</td><td>{{ $pricing->deslocamento }}</td></tr>
            @endif
        </table>
    </div>

    {{-- Parâmetros --}}
    <div class="params">
        <span><strong>Margem de Lucro:</strong> {{ number_format((float)$pricing->margem_lucro, 2, ',', '.') }}%</span>
        <span><strong>Imposto (NFSe):</strong> {{ number_format((float)$pricing->percentual_imposto, 2, ',', '.') }}%</span>
        <span><strong>Parcelas:</strong> {{ $pricing->quantidade_parcelas }}</span>
    </div>

    {{-- Calculadora NR-1 --}}
    @php
        $margem  = (float)$pricing->margem_lucro / 100;
        $imposto = (float)$pricing->percentual_imposto / 100;
        $f       = 1 + $margem;

        $custoAplicacao = (int)$pricing->num_funcionarios * (float)$pricing->valor_por_funcionario;

        $linhas = [
            ['Encontro',   (float)$pricing->despesa_encontro,  true],
            ['Aplicação',  $custoAplicacao,                    true],
            ['Risco',      (float)$pricing->despesa_risco,     true],
            ['Relatório',  (float)$pricing->despesa_relatorio, true],
            ['Despesas',   (float)$pricing->despesas_indiretas, false],
            ['Ação Anual', (float)$pricing->despesa_acao_anual, true],
        ];

        $totalCusto  = collect($linhas)->sum(fn($l) => $l[1]);
        $totalSImp   = collect($linhas)->sum(fn($l) => $l[2] ? $l[1] * $f : $l[1]);
        $totalCImp   = $totalSImp * (1 + $imposto);
        $parcelas    = max(1, (int)$pricing->quantidade_parcelas);
        $lucroFinal  = $totalCImp - $totalCusto;
    @endphp

    <div class="section">
        <div class="section-title">Composição de Serviços</div>
        <table class="calc">
            <thead>
                <tr>
                    <th>Serviço</th>
                    <th class="right">Custo Médio</th>
                    <th class="right">Lucro</th>
                    <th class="right">Total s/ Imposto</th>
                </tr>
            </thead>
            <tbody>
                @foreach($linhas as [$nome, $custo, $temLucro])
                @php
                    $lucro = $temLucro ? $custo * $margem : 0;
                    $total = $temLucro ? $custo * $f      : $custo;
                @endphp
                <tr class="{{ $temLucro ? '' : 'despesas' }}">
                    <td>
                        {{ $nome }}
                        @if($nome === 'Aplicação' && $pricing->num_funcionarios)
                            <br><small style="color:#888">{{ $pricing->num_funcionarios }} func. × R$ {{ number_format((float)$pricing->valor_por_funcionario,2,',','.') }}</small>
                        @endif
                    </td>
                    <td class="right">R$ {{ number_format($custo, 2, ',', '.') }}</td>
                    <td class="right">{{ $temLucro ? 'R$ ' . number_format($lucro, 2, ',', '.') : '—' }}</td>
                    <td class="right">R$ {{ number_format($total, 2, ',', '.') }}</td>
                </tr>
                @endforeach
                <tr class="total">
                    <td>TOTAL</td>
                    <td class="right">R$ {{ number_format($totalCusto, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format($totalSImp - $totalCusto, 2, ',', '.') }}</td>
                    <td class="right">R$ {{ number_format($totalSImp, 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Resumo --}}
    <div class="section">
        <div class="section-title">Resumo Financeiro</div>
        <table class="resumo">
            <tr><td class="lbl">Total Custo Médio</td><td class="val">R$ {{ number_format($totalCusto, 2, ',', '.') }}</td></tr>
            <tr><td class="lbl">Total s/ Imposto</td><td class="val">R$ {{ number_format($totalSImp, 2, ',', '.') }}</td></tr>
            <tr class="c-imposto">
                <td class="lbl">Total c/ Imposto ({{ number_format((float)$pricing->percentual_imposto, 2, ',', '.') }}%)</td>
                <td class="val">R$ {{ number_format($totalCImp, 2, ',', '.') }}</td>
            </tr>
            @if($parcelas > 1)
            <tr class="parcela">
                <td class="lbl">Valor por Parcela ({{ $parcelas }}×)</td>
                <td class="val">R$ {{ number_format($totalCImp / $parcelas, 2, ',', '.') }}</td>
            </tr>
            @endif
            <tr class="lucro">
                <td class="lbl">Lucro Final</td>
                <td class="val">R$ {{ number_format($lucroFinal, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    @if($pricing->observacoes)
    <div class="section">
        <div class="section-title">Observações</div>
        <p>{{ $pricing->observacoes }}</p>
    </div>
    @endif

    <div class="footer">
        Instituto Alves Neves — Sistema de Gestão Isabelle — {{ now()->format('d/m/Y') }}
    </div>
</body>
</html>
