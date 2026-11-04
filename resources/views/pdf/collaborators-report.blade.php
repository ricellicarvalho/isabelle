<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Relatório de Colaboradores</title>
    <style>
        @page { margin: 18mm 12mm 16mm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #222; }
        .header { width: 100%; border-bottom: 2px solid #1e4a5c; padding-bottom: 8px; margin-bottom: 10px; }
        .header td { border: 0; padding: 0; }
        .logo { width: 92px; max-height: 55px; }
        h1 { color: #1e4a5c; font-size: 16px; margin: 0 0 3px; text-align: right; }
        .generated { color: #666; font-size: 8px; text-align: right; }
        .filters { background: #f3f7f8; border-left: 3px solid #1e4a5c; padding: 6px 8px; margin-bottom: 12px; line-height: 1.7; }
        .client { margin-bottom: 12px; page-break-inside: avoid; }
        .client-title { background: #1e4a5c; color: white; font-size: 10px; font-weight: bold; padding: 6px 7px; }
        .client-count { float: right; font-size: 8px; font-weight: normal; }
        table.items { width: 100%; border-collapse: collapse; }
        .items th { background: #eaf0f2; color: #1e4a5c; padding: 5px 4px; text-transform: uppercase; font-size: 8px; }
        .items td { border-bottom: 1px solid #ddd; padding: 5px 4px; vertical-align: top; }
        .items tr:nth-child(even) td { background: #f7f7f7; }
        .left { text-align: left; }
        .center { text-align: center; }
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
                <h1>Relatório de Colaboradores</h1>
                <div class="generated">Gerado em {{ now()->format('d/m/Y \à\s H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="filters">
        <strong>Cliente:</strong> {{ $report['filters']['cliente'] ?? 'Todos' }} &nbsp;|&nbsp;
        <strong>Pesquisa:</strong> {{ $report['filters']['search'] ?: 'Sem filtro' }} &nbsp;|&nbsp;
        <strong>Clientes:</strong> {{ $report['clients_count'] }} &nbsp;|&nbsp;
        <strong>Colaboradores:</strong> {{ $report['count'] }}
    </div>

    @if (empty($report['items']))
        <div class="empty">Nenhum colaborador encontrado para os filtros selecionados.</div>
    @else
        @foreach (collect($report['items'])->groupBy('client_id') as $clientItems)
            <div class="client">
                <div class="client-title">
                    {{ $clientItems->first()['cliente'] }}
                    <span class="client-count">
                        {{ $clientItems->count() }} {{ $clientItems->count() === 1 ? 'colaborador' : 'colaboradores' }}
                    </span>
                </div>
                <table class="items">
                    <thead>
                        <tr>
                            <th class="left" style="width:35%;">Colaborador</th>
                            <th class="left" style="width:20%;">Telefone</th>
                            <th class="left" style="width:27%;">Local / Setor</th>
                            <th class="center" style="width:18%;">Cadastrado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($clientItems as $item)
                            <tr>
                                <td>{{ $item['nome'] }}</td>
                                <td>{{ $item['telefone'] ?: '—' }}</td>
                                <td>{{ $item['local'] ?: '—' }}</td>
                                <td class="center">{{ $item['cadastrado_em'] ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <div class="footer">Relatório gerado pelo sistema.</div>
</body>
</html>
