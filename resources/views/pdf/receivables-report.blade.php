<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Contas a Receber</title>
    <style>
        @page { margin: 18mm 12mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        .header { width: 100%; border-bottom: 2px solid #1e4a5c; padding-bottom: 8px; margin-bottom: 10px; }
        .header td { border: 0; padding: 0; }
        .logo { width: 92px; max-height: 55px; }
        h1 { color: #1e4a5c; font-size: 16px; margin: 0 0 3px; text-align: right; }
        .generated { color: #666; font-size: 8px; text-align: right; }
        .filters { background: #f3f7f8; border-left: 3px solid #1e4a5c; padding: 6px 8px; margin-bottom: 10px; line-height: 1.7; }
        .summary { text-align: right; color: #1e4a5c; font-size: 12px; font-weight: bold; margin-bottom: 8px; }
        table.items { width: 100%; border-collapse: collapse; }
        .items th { background: #1e4a5c; color: white; padding: 6px 4px; text-transform: uppercase; font-size: 8px; }
        .items td { border-bottom: 1px solid #ddd; padding: 5px 4px; vertical-align: top; }
        .items tr:nth-child(even) td { background: #f7f7f7; }
        .left { text-align: left; }
        .center { text-align: center; }
        .right { text-align: right; }
        .total td { border-top: 2px solid #1e4a5c; background: #eaf0f2 !important; font-weight: bold; font-size: 10px; }
        .empty { color: #888; font-style: italic; padding: 25px; text-align: center; }
        .footer { color: #777; font-size: 8px; margin-top: 10px; text-align: right; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td style="width:35%;">
                @if ($logoBase64)
                    <img class="logo" src="{{ $logoBase64 }}" alt="Logomarca">
                @endif
            </td>
            <td style="width:65%;">
                <h1>Relatório de Contas a Receber</h1>
                <div class="generated">Gerado em {{ now()->format('d/m/Y \à\s H:i') }}</div>
            </td>
        </tr>
    </table>

    @php
        $filters = $report['filters'];
        $statusLabel = match ($filters['status']) {
            'pendente' => 'Pendente',
            'pago' => 'Pago',
            'cancelado' => 'Cancelado',
            'vencido' => 'Vencido',
            default => 'Todos',
        };
    @endphp

    <div class="filters">
        <strong>Cliente:</strong> {{ $filters['cliente'] ?? 'Todos' }} &nbsp;|&nbsp;
        <strong>Contrato:</strong> {{ $filters['contrato'] ?? 'Todos' }} &nbsp;|&nbsp;
        <strong>Status:</strong> {{ $statusLabel }}<br>
        <strong>Vencimento:</strong>
        {{ $filters['data_inicio'] ? \Illuminate\Support\Carbon::parse($filters['data_inicio'])->format('d/m/Y') : 'Início' }}
        a
        {{ $filters['data_fim'] ? \Illuminate\Support\Carbon::parse($filters['data_fim'])->format('d/m/Y') : 'Fim' }}
        &nbsp;|&nbsp; <strong>Registros:</strong> {{ $report['count'] }}
    </div>

    <div class="summary">Valor total: R$ {{ number_format($report['total'], 2, ',', '.') }}</div>

    @if (empty($report['items']))
        <div class="empty">Nenhuma conta a receber encontrada para os filtros selecionados.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th class="left" style="width:22%;">Cliente</th>
                    <th class="left" style="width:12%;">Contrato</th>
                    <th class="left" style="width:25%;">Descrição</th>
                    <th class="center" style="width:8%;">Parcela</th>
                    <th class="center" style="width:12%;">Vencimento</th>
                    <th class="center" style="width:10%;">Status</th>
                    <th class="right" style="width:11%;">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['items'] as $item)
                    <tr>
                        <td>{{ $item['cliente'] }}</td>
                        <td>{{ $item['contrato'] }}</td>
                        <td>{{ $item['descricao'] }}</td>
                        <td class="center">{{ $item['parcela'] ?? '—' }}</td>
                        <td class="center">{{ $item['vencimento'] }}</td>
                        <td class="center">{{ ucfirst($item['status']) }}</td>
                        <td class="right">R$ {{ number_format($item['valor'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="6">Total geral</td>
                    <td class="right">R$ {{ number_format($report['total'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">Relatório gerado pelo sistema.</div>
</body>
</html>
