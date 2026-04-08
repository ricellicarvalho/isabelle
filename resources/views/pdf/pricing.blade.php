<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Precificação — {{ $pricing->nome }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #222; margin: 20px; }
        h1 { font-size: 18px; margin: 0 0 4px 0; color: #1e084a; }
        .subtitle { font-size: 11px; color: #666; margin-bottom: 20px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 13px; font-weight: bold; color: #1e084a; border-bottom: 2px solid #1e084a; padding-bottom: 4px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 6px 8px; border-bottom: 1px solid #eee; }
        td.label { font-weight: bold; width: 45%; color: #444; }
        td.value { text-align: right; }
        tr.total td { background: #f0f0f0; font-weight: bold; border-top: 2px solid #999; }
        tr.preco td { background: #1e084a; color: #fff; font-weight: bold; font-size: 14px; border-top: 3px solid #1e084a; }
        tr.lucro td { background: #d4edda; color: #155724; font-weight: bold; }
        .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 10px; }
        .badge-purple { background: #ede9fe; color: #5b21b6; }
        .footer { margin-top: 30px; font-size: 9px; color: #aaa; text-align: center; border-top: 1px solid #eee; padding-top: 8px; }
    </style>
</head>
<body>
    <h1>Precificação de Serviço</h1>
    <div class="subtitle">Gerado em {{ now()->format('d/m/Y H:i') }}</div>

    <div class="section">
        <div class="section-title">Identificação</div>
        <table>
            <tr>
                <td class="label">Serviço / Ação</td>
                <td>{{ $pricing->nome }}</td>
            </tr>
            <tr>
                <td class="label">Categoria</td>
                <td>{{ $pricing->category?->descricao ?? '—' }}</td>
            </tr>
            @if($pricing->descricao)
            <tr>
                <td class="label">Descrição</td>
                <td>{{ $pricing->descricao }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section">
        <div class="section-title">Composição de Custos</div>
        <table>
            <tr>
                <td class="label">Custo Direto</td>
                <td class="value">R$ {{ number_format((float)$pricing->custo_direto, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="label">Custo Indireto</td>
                <td class="value">R$ {{ number_format((float)$pricing->custo_indireto, 2, ',', '.') }}</td>
            </tr>
            <tr class="total">
                <td class="label">Custo Total</td>
                <td class="value">R$ {{ number_format((float)$pricing->custo_direto + (float)$pricing->custo_indireto, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Resultado</div>
        <table>
            <tr>
                <td class="label">Margem de Lucro</td>
                <td class="value">{{ number_format((float)$pricing->margem_lucro, 2, ',', '.') }}%</td>
            </tr>
            <tr class="preco">
                <td class="label">Preço de Venda</td>
                <td class="value">R$ {{ number_format((float)$pricing->preco_venda, 2, ',', '.') }}</td>
            </tr>
            <tr class="lucro">
                <td class="label">Lucro por Venda</td>
                <td class="value">R$ {{ number_format((float)$pricing->preco_venda - (float)$pricing->custo_direto - (float)$pricing->custo_indireto, 2, ',', '.') }}</td>
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
