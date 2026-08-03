<?php

use App\Http\Controllers\PrecadastroController;
use App\Models\BankBoleto;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Contract;
use App\Models\Nfse;
use App\Models\Pricing;
use App\Services\BankBoletoService;
use App\Services\PayablesReportService;
use App\Services\PaymentsReportService;
use App\Services\ReceivablesReportService;
use App\Support\PortalAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/precadastro/{token}', [PrecadastroController::class, 'show'])->name('precadastro');
Route::post('/precadastro/{token}', [PrecadastroController::class, 'submit'])->name('precadastro.submit');

// Download de precificação em PDF — URL assinada com validade de 30 min
Route::get('/pricing/{pricing}/pdf', function (Pricing $pricing) {
    $pricing->load('category');

    $timbradoPath = public_path('images/timbrado.jpg');
    $timbradoBase64 = file_exists($timbradoPath)
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($timbradoPath))
        : null;

    $pdf = Pdf::loadView('pdf.pricing', [
        'pricing'         => $pricing,
        'timbradoBase64'  => $timbradoBase64,
    ]);

    return response()->streamDownload(
        fn () => print($pdf->output()),
        'precificacao-' . Str::slug($pricing->nome) . '.pdf',
        ['Content-Type' => 'application/pdf']
    );
})->name('pricing.pdf')->middleware(['signed', 'auth:web']);

// Download de boleto em PDF — requer URL assinada para evitar acesso direto não autorizado
Route::get('/boleto/{boleto}/pdf', function (BankBoleto $boleto) {
    $pdf = BankBoletoService::renderPdf($boleto);

    return response($pdf, 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="boleto-' . $boleto->nosso_numero . '.pdf"',
    ]);
})->name('boleto.pdf')->middleware(['signed', 'auth:web']);

// Download de NFSe em PDF — URL assinada com validade de 30 min
Route::get('/nfse/{nfse}/pdf', function (Nfse $nfse) {
    abort_unless(filled($nfse->pdf), 404, 'PDF não disponível para esta NFSe.');

    $numero = $nfse->numero ?? "RPS-{$nfse->numero_rps}";

    return response(hex2bin($nfse->pdf), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => "inline; filename=\"NFSe-{$numero}.pdf\"",
    ]);
})->name('nfse.pdf')->middleware(['signed', 'auth:web']);

// Download de NFSe em XML — URL assinada com validade de 30 min
Route::get('/nfse/{nfse}/xml', function (Nfse $nfse) {
    abort_unless(filled($nfse->xml), 404, 'XML não disponível para esta NFSe.');

    $numero = $nfse->numero ?? "RPS-{$nfse->numero_rps}";

    return response(hex2bin($nfse->xml), 200, [
        'Content-Type'        => 'application/xml',
        'Content-Disposition' => "attachment; filename=\"NFSe-{$numero}.xml\"",
    ]);
})->name('nfse.xml')->middleware(['signed', 'auth:web']);

// Relatório de contratos a vencer em PDF — abre inline em nova aba, URL assinada
Route::get('/relatorios/contratos-a-vencer/pdf', function (Request $request) {
    $prazo      = $request->get('prazo', '30');
    $cliente    = $request->get('cliente', '');
    $dataInicio = $request->get('data_inicio');
    $dataFim    = $request->get('data_fim');

    $query = Contract::query()
        ->where('status', 'ativo')
        ->with('client')
        ->orderBy('data_fim');

    if ($dataInicio && $dataFim) {
        $query->whereDate('data_fim', '>=', $dataInicio)
              ->whereDate('data_fim', '<=', $dataFim);
    } else {
        $query->whereDate('data_fim', '>=', today())
              ->whereDate('data_fim', '<=', today()->addDays((int) $prazo));
    }

    if (filled($cliente)) {
        $query->whereHas('client', fn ($q) => $q->where('razao_social', 'like', "%{$cliente}%"));
    }

    $contracts = $query->get()->map(function (Contract $c): array {
        return [
            'id'             => $c->id,
            'numero'         => $c->numero,
            'cliente'        => $c->client?->razao_social ?? '—',
            'tipo_servico'   => $c->tipo_servico,
            'valor_total'    => (float) $c->valor_total,
            'data_fim'       => $c->data_fim?->format('d/m/Y'),
            'dias_restantes' => (int) today()->diffInDays($c->data_fim, false),
        ];
    })->toArray();

    $timbradoPath   = public_path('images/timbrado.jpg');
    $timbradoBase64 = file_exists($timbradoPath)
        ? 'data:image/jpeg;base64,' . base64_encode(file_get_contents($timbradoPath))
        : null;

    $pdf = Pdf::loadView('pdf.expiring-contracts', compact('contracts', 'prazo', 'cliente', 'dataInicio', 'dataFim', 'timbradoBase64'))
        ->setPaper('a4', 'portrait');

    return response($pdf->output(), 200, [
        'Content-Type'        => 'application/pdf',
        'Content-Disposition' => 'inline; filename="contratos-a-vencer-' . now()->format('Y-m-d') . '.pdf"',
    ]);
})->name('reports.expiring-contracts.pdf')->middleware(['signed', 'auth:web']);

// Relatório de Contas a Receber em PDF — abre inline em nova aba, URL assinada
Route::get('/relatorios/contas-a-receber/pdf', function (Request $request) {
    $filters = $request->only(['client_id', 'contract_id', 'data_inicio', 'data_fim', 'status']);
    $report = ReceivablesReportService::generate($filters);

    $logoPath = public_path('images/logo.png');
    $logoBase64 = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $pdf = Pdf::loadView('pdf.receivables-report', compact('report', 'logoBase64'))
        ->setPaper('a4', 'landscape');

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="contas-a-receber-' . now()->format('Y-m-d') . '.pdf"',
    ]);
})->name('reports.receivables.pdf')->middleware(['signed', 'auth:web']);

// Relatório de Contas a Pagar em PDF — abre inline em nova aba, URL assinada
Route::get('/relatorios/contas-a-pagar/pdf', function (Request $request) {
    $filters = $request->only(['supplier_id', 'category_id', 'data_inicio', 'data_fim', 'status']);
    $report = PayablesReportService::generate($filters);

    $logoPath = public_path('images/logo.png');
    $logoBase64 = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $pdf = Pdf::loadView('pdf.payables-report', compact('report', 'logoBase64'))
        ->setPaper('a4', 'landscape');

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="contas-a-pagar-' . now()->format('Y-m-d') . '.pdf"',
    ]);
})->name('reports.payables.pdf')->middleware(['signed', 'auth:web']);

// Relatório de Pagamentos em PDF — abre inline em nova aba, URL assinada
Route::get('/relatorios/pagamentos/pdf', function (Request $request) {
    $filters = $request->only(['supplier_id', 'category_id', 'data_inicio', 'data_fim', 'forma_pagamento']);
    $report = PaymentsReportService::generate($filters);

    $logoPath = public_path('images/logo.png');
    $logoBase64 = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $pdf = Pdf::loadView('pdf.payments-report', compact('report', 'logoBase64'))
        ->setPaper('a4', 'landscape');

    return response($pdf->output(), 200, [
        'Content-Type' => 'application/pdf',
        'Content-Disposition' => 'inline; filename="pagamentos-' . now()->format('Y-m-d') . '.pdf"',
    ]);
})->name('reports.payments.pdf')->middleware(['signed', 'auth:web']);

Route::post('/portal/select-client', function (Request $request) {
    $userId = Auth::guard('portal')->id();
    abort_unless($userId, 403);

    $data = $request->validate([
        'client_id' => ['required', 'integer'],
    ]);

    abort_unless(PortalAccess::selectClient($userId, (int) $data['client_id']), 403);

    return back();
})->name('portal.select-client')->middleware(['auth:portal']);

// Servir arquivos de documentos do portal com suporte a visualização inline
Route::get('/portal/documents/{document}/file/{index}', function (ClientDocument $document, int $index) {
    $userId = Auth::guard('portal')->id();
    $client = PortalAccess::client($userId);
    $escopo = PortalAccess::scope($userId);
    $tiposFinanceiro = ['boleto', 'nota_fiscal'];
    $tipoCompativel = $escopo === 'financeiro'
        ? in_array($document->tipo, $tiposFinanceiro, true)
        : ! in_array($document->tipo, $tiposFinanceiro, true);

    abort_unless($client && $document->client_id === $client->id && $document->visivel_portal && $tipoCompativel, 403);

    $arquivos = (array) ($document->caminho_arquivo ?? []);
    $path = $arquivos[$index] ?? null;
    abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $mimeMap = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'mp4'  => 'video/mp4',
        'webm' => 'video/webm',
        'ogg'  => 'video/ogg',
    ];

    $mimeType = $mimeMap[$ext] ?? (Storage::disk('local')->mimeType($path) ?: 'application/octet-stream');
    $disposition = isset($mimeMap[$ext]) ? 'inline' : 'attachment';

    return response(Storage::disk('local')->get($path), 200, [
        'Content-Type'        => $mimeType,
        'Content-Disposition' => $disposition . '; filename="' . basename($path) . '"',
        'Cache-Control'       => 'private, max-age=3600',
    ]);
})->name('portal.document.file')->middleware(['auth:portal']);
