<?php

use App\Http\Controllers\PrecadastroController;
use App\Models\BankBoleto;
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
