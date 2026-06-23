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

        $client->load('colaboradores');

        $colaboradoresIniciais = $client->colaboradores->map(fn (Colaborador $c): array => [
            'nome'     => $c->nome,
            'telefone' => $c->telefone ?? '',
            'local'    => $c->local ?? '',
        ])->values()->all();

        $telefonesIniciais = collect($client->telefones ?? [])->map(fn (array $t): array => [
            'tipo'   => $t['tipo'] ?? 'celular',
            'numero' => $this->formatarTelefoneExibicao($t['numero'] ?? ''),
        ])->values()->all();

        return view('precadastro.form', [
            'estado'                => 'aberto',
            'client'                => $client,
            'token'                 => $token,
            'colaboradoresIniciais' => $colaboradoresIniciais,
            'telefonesIniciais'     => $telefonesIniciais,
        ]);
    }

    /**
     * Formata um telefone (apenas dígitos) para exibição, usando a mesma regra
     * do formulário: acima de 10 dígitos é celular (5+4), senão fixo (4+4).
     */
    private function formatarTelefoneExibicao(string $numero): string
    {
        $digitos = preg_replace('/\D/', '', $numero);
        $ddd = substr($digitos, 0, 2);
        $resto = substr($digitos, 2);

        if ($resto === '') {
            return $ddd;
        }

        $ehCelular = strlen($digitos) > 10;
        $tamanhoMeio = $ehCelular ? 5 : 4;
        $meio = substr($resto, 0, $tamanhoMeio);
        $fim = substr($resto, $tamanhoMeio, 4);

        return $fim !== '' ? "({$ddd}) {$meio}-{$fim}" : "({$ddd}) {$meio}";
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

        $dados = $request->validate([
            'nome_fantasia'            => ['nullable', 'string', 'max:255'],
            'inscricao_estadual'       => ['nullable', 'string', 'max:255'],
            'inscricao_municipal'      => ['nullable', 'string', 'max:255'],
            'cep'                      => ['required', 'string', 'max:9'],
            'endereco'                 => ['required', 'string', 'max:255'],
            'numero'                   => ['required', 'string', 'max:20'],
            'complemento'              => ['nullable', 'string', 'max:255'],
            'bairro'                   => ['required', 'string', 'max:255'],
            'uf'                       => ['required', 'string', 'size:2'],
            'cidade'                   => ['required', 'string', 'max:255'],
            'contato_nome'             => ['required', 'string', 'max:255'],
            'email'                    => ['required', 'email:rfc,dns', 'max:255'],
            'contato_financeiro_nome'  => ['required', 'string', 'max:255'],
            'contato_financeiro_email' => ['required', 'email:rfc,dns', 'max:255'],
        ], [], [
            'nome_fantasia'            => 'Nome Fantasia',
            'inscricao_estadual'       => 'Inscrição Estadual',
            'inscricao_municipal'      => 'Inscrição Municipal',
            'cep'                      => 'CEP',
            'endereco'                 => 'Logradouro',
            'numero'                   => 'Número',
            'complemento'              => 'Complemento',
            'bairro'                   => 'Bairro',
            'uf'                       => 'UF',
            'cidade'                   => 'Cidade',
            'contato_nome'             => 'Nome do responsável pelas documentações',
            'email'                    => 'E-mail do responsável pelas documentações',
            'contato_financeiro_nome'  => 'Nome do responsável pelo financeiro',
            'contato_financeiro_email' => 'E-mail do responsável pelo financeiro',
        ]);

        $telefones = json_decode($request->input('telefones_json', '[]'), true);
        $telefones = array_values(array_filter(array_map(function ($t) {
            $numero = preg_replace('/\D/', '', $t['numero'] ?? '');

            return $numero === '' ? null : [
                'tipo'   => $t['tipo'] ?? 'celular',
                'numero' => $numero,
            ];
        }, (array) $telefones)));

        if (empty($telefones)) {
            return redirect()->route('precadastro', $token)
                ->withErrors(['telefones' => 'Adicione pelo menos um telefone antes de enviar.'])
                ->withInput();
        }

        $colaboradores = json_decode($request->input('colaboradores_json', '[]'), true);

        if (empty($colaboradores)) {
            return redirect()->route('precadastro', $token)
                ->withErrors(['colaboradores' => 'Adicione pelo menos um colaborador antes de enviar.'])
                ->withInput();
        }

        // Substitui a lista de colaboradores a cada envio, para que uma correção
        // feita após reabertura do pré-cadastro não duplique os registros antigos.
        $client->colaboradores()->delete();

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

        $client->update([
            ...$dados,
            'telefones'           => $telefones,
            'cadastro_preenchido' => true,
        ]);

        return redirect()->route('precadastro', $token)->with('enviado', true);
    }
}
