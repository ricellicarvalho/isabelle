<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { font-family: Arial, sans-serif; color: #333; margin: 0; padding: 0; background: #f4f4f4; }
        .container { max-width: 600px; margin: 30px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,.1); }
        .header { background: #8751d4; color: #fff; padding: 28px 32px; }
        .header h1 { margin: 0; font-size: 22px; }
        .header p { margin: 4px 0 0; font-size: 14px; opacity: .85; }
        .body { padding: 28px 32px; }
        .info-block { background: #f9f9f9; border-left: 4px solid #8751d4; padding: 12px 16px; border-radius: 4px; margin: 16px 0; }
        .info-block p { margin: 4px 0; font-size: 14px; }
        .info-block strong { color: #555; }
        .footer { background: #f0f0f0; padding: 16px 32px; font-size: 12px; color: #888; text-align: center; }
        .badge { display: inline-block; background: #8751d4; color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Nota Fiscal de Serviço Eletrônica</h1>
        <p>{{ config('app.name') }}</p>
    </div>

    <div class="body">
        <p>Prezado(a), segue a Nota Fiscal de Serviço Eletrônica (NFSe) emitida.</p>

        <div class="info-block">
            <p><strong>Número da NFSe:</strong> <span class="badge">{{ $nfse->numero ?? 'Em processamento' }}</span></p>
            <p><strong>Data de Emissão:</strong> {{ $nfse->data_emissao?->format('d/m/Y H:i') ?? now()->format('d/m/Y H:i') }}</p>
            <p><strong>Competência:</strong> {{ $nfse->competencia?->format('m/Y') ?? '—' }}</p>
            <p><strong>Tomador:</strong> {{ $client?->razao_social ?? '—' }}</p>
            @if($contract)
            <p><strong>Contrato:</strong> {{ $contract->numero }}</p>
            @endif
            <p><strong>Discriminação:</strong> {{ $nfse->discriminacao }}</p>
        </div>

        <div class="info-block">
            <p><strong>Valor do Serviço:</strong> R$ {{ number_format((float) $nfse->valor, 2, ',', '.') }}</p>
            <p><strong>Alíquota ISS:</strong> {{ number_format((float) $nfse->aliquota, 2, ',', '.') }}%</p>
            @if($nfse->valor_iss)
            <p><strong>Valor ISS:</strong> R$ {{ number_format((float) $nfse->valor_iss, 2, ',', '.') }}</p>
            @endif
            <p><strong>ISS Retido:</strong> {{ $nfse->iss_retido === '1' ? 'Sim' : 'Não' }}</p>
        </div>

        @if($nfse->codigo_verificacao)
        <div class="info-block">
            <p><strong>Código de Verificação:</strong> {{ $nfse->codigo_verificacao }}</p>
        </div>
        @endif

        <p style="font-size: 13px; color: #666; margin-top: 20px;">
            O PDF e o XML da nota estão anexados a este e-mail.
            @if($nfse->ambiente === '2')
            <br><em style="color: #e74c3c;">⚠ Esta é uma nota de <strong>homologação</strong> (sem valor fiscal).</em>
            @endif
        </p>
    </div>

    <div class="footer">
        <p>{{ config('app.name') }} · Este é um e-mail automático, não responda a esta mensagem.</p>
    </div>
</div>
</body>
</html>
