<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Colaborador;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.precadastro')]
class PrecadastroForm extends Component
{
    public ?Client $client = null;

    public bool $tokenInvalido  = false;
    public bool $cadastroJaFeito = false;
    public bool $enviado        = false;

    // Lista acumulada antes de enviar
    public array $colaboradores = [];

    // Campos do formulário de adição/edição
    public string $novoNome      = '';
    public string $novoTelefone  = '';
    public string $novoLocal     = '';

    // >= 0 = editando item existente; -1 = adicionando novo
    public int $editandoIndex = -1;

    public function mount(string $token): void
    {
        $client = Client::where('cadastro_token', $token)
            ->where(function ($query) {
                $query->whereNull('cadastro_token_expira_em')
                    ->orWhere('cadastro_token_expira_em', '>', now());
            })
            ->first();

        if (! $client) {
            $this->tokenInvalido = true;
            return;
        }

        if ($client->cadastro_preenchido) {
            $this->cadastroJaFeito = true;
            $this->client = $client;
            return;
        }

        $this->client = $client;
    }

    public function adicionarOuAtualizar(): void
    {
        $this->validate(
            [
                'novoNome'     => 'required|string|max:255',
                'novoTelefone' => 'nullable|string|max:20',
                'novoLocal'    => 'nullable|string|max:255',
            ],
            [
                'novoNome.required' => 'O nome do colaborador é obrigatório.',
                'novoNome.max'      => 'O nome não pode ter mais de 255 caracteres.',
            ]
        );

        $item = [
            'nome'     => trim($this->novoNome),
            'telefone' => trim($this->novoTelefone) ?: null,
            'local'    => trim($this->novoLocal) ?: null,
        ];

        if ($this->editandoIndex >= 0) {
            $this->colaboradores[$this->editandoIndex] = $item;
            $this->editandoIndex = -1;
        } else {
            $this->colaboradores[] = $item;
        }

        $this->novoNome     = '';
        $this->novoTelefone = '';
        $this->novoLocal    = '';
    }

    public function editarColaborador(int $index): void
    {
        $this->editandoIndex = $index;
        $this->novoNome      = $this->colaboradores[$index]['nome']     ?? '';
        $this->novoTelefone  = $this->colaboradores[$index]['telefone'] ?? '';
        $this->novoLocal     = $this->colaboradores[$index]['local']    ?? '';
        $this->resetValidation();
    }

    public function removerColaborador(int $index): void
    {
        unset($this->colaboradores[$index]);
        $this->colaboradores = array_values($this->colaboradores);

        if ($this->editandoIndex === $index) {
            $this->cancelarEdicao();
        }
    }

    public function cancelarEdicao(): void
    {
        $this->editandoIndex = -1;
        $this->novoNome      = '';
        $this->novoTelefone  = '';
        $this->novoLocal     = '';
        $this->resetValidation();
    }

    public function salvar(): void
    {
        if (empty($this->colaboradores)) {
            $this->addError('colaboradores', 'Adicione pelo menos um colaborador antes de enviar.');
            return;
        }

        foreach ($this->colaboradores as $dado) {
            Colaborador::create([
                'client_id' => $this->client->id,
                'nome'      => $dado['nome'],
                'telefone'  => $dado['telefone'] ?? null,
                'local'     => $dado['local']    ?? null,
            ]);
        }

        $this->client->update(['cadastro_preenchido' => true]);
        $this->enviado = true;
    }

    public function render()
    {
        return view('livewire.precadastro-form');
    }
}
