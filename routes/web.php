<?php

use App\Http\Controllers\PrecadastroController;
use App\Models\BankBoleto;
use App\Models\Client;
use App\Models\ClientDocument;
use App\Models\Nfse;
use App\Models\Pricing;
use App\Services\BankBoletoService;
use Barryvdh\DomPDF\Facade\Pdf;
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

// Servir arquivos de documentos do portal com suporte a visualização inline
Route::get('/portal/documents/{document}/file/{index}', function (ClientDocument $document, int $index) {
    $client = Client::where('portal_user_id', Auth::guard('portal')->id())->first();
    abort_unless($client && $document->client_id === $client->id && $document->visivel_portal, 403);

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
