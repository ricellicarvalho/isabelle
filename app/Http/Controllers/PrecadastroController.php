<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Colaborador;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrecadastroController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $client = Client::where('cadastro_token', $token)
            ->where(function ($q) {
                $q->whereNull('cadastro_token_expira_em')
                  ->orWhere('cadastro_token_expira_em', '>', now());
            })
            ->first();

        if (! $client) {
            return view('precadastro.form', ['estado' => 'token_invalido', 'client' => null, 'token' => $token]);
        }

        if ($client->cadastro_preenchido) {
            return view('precadastro.form', ['estado' => 'ja_preenchido', 'client' => $client, 'token' => $token]);
        }

        return view('precadastro.form', ['estado' => 'aberto', 'client' => $client, 'token' => $token]);
    }

    public function submit(Request $request, string $token): RedirectResponse
    {
        $client = Client::where('cadastro_token', $token)
            ->where(function ($q) {
                $q->whereNull('cadastro_token_expira_em')
                  ->orWhere('cadastro_token_expira_em', '>', now());
            })
            ->whereRaw('cadastro_preenchido = 0')
            ->first();

        if (! $client) {
            return redirect()->route('precadastro', $token);
        }

        $colaboradores = json_decode($request->input('colaboradores_json', '[]'), true);

        if (empty($colaboradores)) {
            return redirect()->route('precadastro', $token)
                ->withErrors(['colaboradores' => 'Adicione pelo menos um colaborador antes de enviar.'])
                ->withInput();
        }

        foreach ($colaboradores as $dado) {
            $nome = trim($dado['nome'] ?? '');
            if ($nome === '') {
                continue;
            }

            Colaborador::create([
                'client_id' => $client->id,
                'nome'      => $nome,
                'telefone'  => trim($dado['telefone'] ?? '') ?: null,
                'local'     => trim($dado['local'] ?? '') ?: null,
            ]);
        }

        $client->update(['cadastro_preenchido' => true]);

        return redirect()->route('precadastro', $token)->with('enviado', true);
    }
}
