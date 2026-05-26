<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contratos a Vencer</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            /* top: abaixo da barra teal do timbrado (~30mm)
               bottom: acima da barra de rodapé (~18mm)
               sides: alinhado às bordas do timbrado */
            margin: 34mm 8mm 22mm 8mm;
        }

        h1 {
            font-size: 15px;
            font-weight: bold;
            color: #1e4a5c;
            margin: 0 0 3px 0;
        }

        .subtitle {
            font-size: 9px;
            color: #666;
            margin-bottom: 10px;
        }

        .filtros {
            font-size: 9px;
            color: #444;
            background: rgba(255,255,255,0.75);
            border-left: 3px solid #1e4a5c;
            padding: 5px 8px;
            margin-bottom: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #1e4a5c;
            color: #fff;
            padding: 6px 5px;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-weight: bold;
        }

        thead th.right  { text-align: right; }
        thead th.center { text-align: center; }

        tbody td {
            padding: 5px 5px;
            border-bottom: 1px solid rgba(0,0,0,0.08);
            vertical-align: middle;
            background: rgba(255,255,255,0.80);
        }

        tbody tr:nth-child(even) td {
            background: rgba(245,247,250,0.85);
        }

        td.right  { text-align: right; }
        td.center { text-align: center; }
        td.numero { font-weight: bold; font-family: DejaVu Sans Mono, monospace; font-size: 9px; }

        .badge {
            display: inline-block;
            padding: 2px 7px;
            border-radius: 9999px;
            font-size: 8px;
            font-weight: bold;
        }
        .badge-danger  { background: #fef2f2; color: #dc2626; }
        .badge-warning { background: #fffbeb; color: #b45309; }
        .badge-info    { background: #eff6ff; color: #1d4ed8; }

        tr.total-row td {
            font-weight: bold;
            border-top: 2px solid #1e4a5c;
            background: rgba(30,74,92,0.08);
            padding: 6px 5px;
        }

        .footer {
            margin-top: 14px;
            font-size: 8px;
            color: #888;
            text-align: right;
        }

        .empty {
            text-align: center;
            color: #999;
            padding: 30px;
            font-style: italic;
        }
    </style>
</head>
<body>

    {{-- Timbrado como fundo de página completo --}}
    @if (!empty($timbradoBase64))
    <div style="position:fixed;top:0;left:0;width:100%;height:100%;z-index:-1000;">
        <img src="{{ $timbradoBase64 }}" style="width:100%;height:100%;" />
    </div>
    @endif

    <h1>Contratos a Vencer</h1>
    <div class="subtitle">Instituto Alves Neves — Gerado em {{ now()->format('d/m/Y \à\s H:i') }}</div>

    <div class="filtros">
        @if ($dataInicio && $dataFim)
            <strong>Período:</strong> {{ \Illuminate\Support\Carbon::parse($dataInicio)->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($dataFim)->format('d/m/Y') }}
        @else
            <strong>Contratos que vencem nos próximos {{ $prazo ?? 30 }} dias</strong>
        @endif
        @if (filled($cliente))
            &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Cliente:</strong> {{ $cliente }}
        @endif
        &nbsp;&nbsp;|&nbsp;&nbsp;<strong>Total:</strong> {{ count($contracts) }} {{ count($contracts) === 1 ? 'contrato' : 'contratos' }}
    </div>

    @if (count($contracts) === 0)
        <div class="empty">Nenhum contrato encontrado para os filtros selecionados.</div>
    @else
        <table>
            <thead>
                <tr>
                    <th style="width:18%;">Nº Contrato</th>
                    <th style="width:30%;">Cliente</th>
                    <th style="width:13%;">Serviço</th>
                    <th class="right" style="width:14%;">Valor Total</th>
                    <th class="center" style="width:13%;">Encerramento</th>
                    <th class="center" style="width:12%;">Dias Restantes</th>
                </tr>
            </thead>
            <tbody>
                @php $totalValor = 0; @endphp
                @foreach ($contracts as $contract)
                    @php
                        $totalValor += $contract['valor_total'];
                        $dias = $contract['dias_restantes'];
                        $badgeClass = $dias <= 7 ? 'badge-danger' : ($dias <= 15 ? 'badge-warning' : 'badge-info');
                        $tipoLabel = match($contract['tipo_servico']) {
                            'nr1'         => 'NR-1',
                            'palestra'    => 'Palestra',
                            'consultoria' => 'Consultoria',
                            'treinamento' => 'Treinamento',
                            default       => 'Outro',
                        };
                    @endphp
                    <tr>
                        <td class="numero">{{ $contract['numero'] }}</td>
                        <td>{{ $contract['cliente'] }}</td>
                        <td>{{ $tipoLabel }}</td>
                        <td class="right">R$ {{ number_format($contract['valor_total'], 2, ',', '.') }}</td>
                        <td class="center">{{ $contract['data_fim'] }}</td>
                        <td class="center">
                            <span class="badge {{ $badgeClass }}">{{ $dias }} dias</span>
                        </td>
                    </tr>
                @endforeach

                <tr class="total-row">
                    <td colspan="3">Total geral</td>
                    <td class="right">R$ {{ number_format($totalValor, 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">Relatório gerado pelo sistema em {{ now()->format('d/m/Y H:i:s') }}</div>
    @endif

</body>
</html>
