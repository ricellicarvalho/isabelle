<?php

use App\Http\Controllers\PrecadastroController;
use App\Models\BankBoleto;
use App\Models\Nfse;
use App\Services\BankBoletoService;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/precadastro/{token}', [PrecadastroController::class, 'show'])->name('precadastro');
Route::post('/precadastro/{token}', [PrecadastroController::class, 'submit'])->name('precadastro.submit');

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
