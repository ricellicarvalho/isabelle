<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Movimentação de Contratos</title>
    <style>
        @page { margin: 18mm 10mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #222; }
        .header { width: 100%; border-bottom: 2px solid #1e4a5c; padding-bottom: 8px; margin-bottom: 10px; }
        .header td { border: 0; padding: 0; }
        .logo { width: 92px; max-height: 55px; }
        h1 { color: #1e4a5c; font-size: 15px; margin: 0 0 3px; text-align: right; }
        .generated { color: #666; text-align: right; }
        .filters { background: #f3f7f8; border-left: 3px solid #1e4a5c; padding: 6px 8px; margin-bottom: 8px; line-height: 1.7; }
        .summary { color: #1e4a5c; font-size: 10px; font-weight: bold; margin-bottom: 8px; text-align: right; }
        table.items { width: 100%; border-collapse: collapse; }
        .items th { background: #1e4a5c; color: white; padding: 6px 3px; text-transform: uppercase; }
        .items td { border-bottom: 1px solid #ddd; padding: 5px 3px; vertical-align: top; }
        .items tr:nth-child(even) td { background: #f7f7f7; }
        .left { text-align: left; }
        .center { text-align: center; }
        .right { text-align: right; }
        .movement-badge {
            display: inline-block;
            min-width: 48px;
            border: 1px solid;
            border-radius: 10px;
            padding: 2px 7px;
            font-weight: bold;
            text-align: center;
        }
        .movement-badge-new {
            background: #dcfce7;
            border-color: #86efac;
            color: #166534;
        }
        .movement-badge-renewal {
            background: #dbeafe;
            border-color: #93c5fd;
            color: #1e40af;
        }
        .total td { border-top: 2px solid #1e4a5c; background: #eaf0f2 !important; font-weight: bold; }
        .empty { color: #888; font-style: italic; padding: 25px; text-align: center; }
        .footer { color: #777; margin-top: 10px; text-align: right; }
    </style>
</head>
<body>
    @php
        $filters = $report['filters'];
        $movementLabel = fn (string $type): string => $type === 'renewal' ? 'Renovado' : 'Novo';
        $filterMovementLabel = match ($filters['movement_type']) {
            'new' => 'Novos contratos',
            'renewal' => 'Contratos renovados',
            default => 'Novos e renovados',
        };
        $serviceLabel = fn (string $type): string => match ($type) {
            'nr1' => 'NR-1',
            'palestra' => 'Palestra',
            'consultoria' => 'Consultoria',
            'treinamento' => 'Treinamento',
            default => 'Outro',
        };
    @endphp

    <table class="header">
        <tr>
            <td style="width:30%;">
                @if ($logoBase64)
                    <img class="logo" src="{{ $logoBase64 }}" alt="Logomarca">
                @endif
            </td>
            <td style="width:70%;">
                <h1>Relatório de Movimentação de Contratos</h1>
                <div class="generated">Gerado em {{ now()->format('d/m/Y \à\s H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="filters">
        <strong>Cliente:</strong> {{ $filters['cliente'] ?? 'Todos' }} &nbsp;|&nbsp;
        <strong>Movimentação:</strong> {{ $filterMovementLabel }} &nbsp;|&nbsp;
        <strong>Período:</strong> {{ \Illuminate\Support\Carbon::parse($filters['data_inicio'])->format('d/m/Y') }}
        a {{ \Illuminate\Support\Carbon::parse($filters['data_fim'])->format('d/m/Y') }}
    </div>

    <div class="summary">
        Movimentações: {{ $report['count'] }} &nbsp;|&nbsp;
        Novos: {{ $report['new_count'] }} &nbsp;|&nbsp;
        Renovados: {{ $report['renewal_count'] }} &nbsp;|&nbsp;
        Valor total: R$ {{ number_format($report['total_value'], 2, ',', '.') }}
    </div>

    @if (empty($report['items']))
        <div class="empty">Nenhum contrato encontrado para os filtros selecionados.</div>
    @else
        <table class="items">
            <thead>
                <tr>
                    <th class="center">Movimentação</th>
                    <th class="center">Data</th>
                    <th class="left">Contrato</th>
                    <th class="left">Cliente</th>
                    <th class="left">Serviço</th>
                    <th class="center">Versão</th>
                    <th class="center">Vigência</th>
                    <th class="right">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($report['items'] as $item)
                    <tr>
                        <td class="center">
                            <span class="movement-badge {{ $item['movement_type'] === 'renewal' ? 'movement-badge-renewal' : 'movement-badge-new' }}">
                                {{ $movementLabel($item['movement_type']) }}
                            </span>
                        </td>
                        <td class="center">{{ $item['movement_date'] }}</td>
                        <td>{{ $item['numero'] }}</td>
                        <td>{{ $item['cliente'] }}</td>
                        <td>{{ $serviceLabel($item['tipo_servico']) }}</td>
                        <td class="center">{{ $item['version_number'] }}</td>
                        <td class="center">{{ $item['data_inicio'] }} a {{ $item['data_fim'] }}</td>
                        <td class="right">R$ {{ number_format($item['valor_total'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
                <tr class="total">
                    <td colspan="7">Valor total das movimentações</td>
                    <td class="right">R$ {{ number_format($report['total_value'], 2, ',', '.') }}</td>
                </tr>
            </tbody>
        </table>
    @endif

    <div class="footer">Relatório gerado pelo sistema.</div>
</body>
</html>
