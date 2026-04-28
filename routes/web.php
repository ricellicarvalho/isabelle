<?php

use App\Http\Controllers\PrecadastroController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/precadastro/{token}', [PrecadastroController::class, 'show'])->name('precadastro');
Route::post('/precadastro/{token}', [PrecadastroController::class, 'submit'])->name('precadastro.submit');
