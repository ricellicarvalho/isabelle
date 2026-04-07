<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'tipo_pessoa' => fake()->randomElement(["pj","pf"]),
            'cnpj_cpf' => fake()->regexify('[A-Za-z0-9]{18}'),
            'razao_social' => fake()->word(),
            'nome_fantasia' => fake()->word(),
            'inscricao_estadual' => fake()->word(),
            'inscricao_municipal' => fake()->word(),
            'endereco' => fake()->word(),
            'numero' => fake()->regexify('[A-Za-z0-9]{20}'),
            'complemento' => fake()->word(),
            'bairro' => fake()->word(),
            'cidade' => fake()->word(),
            'uf' => fake()->regexify('[A-Za-z0-9]{2}'),
            'cep' => fake()->regexify('[A-Za-z0-9]{10}'),
            'telefone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'email' => fake()->safeEmail(),
            'contato_nome' => fake()->word(),
            'contato_telefone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'nr1_status' => fake()->randomElement(["pendente","em_andamento","regularizada"]),
            'nr1_checklist' => '{}',
            'portal_user_id' => fake()->word(),
            'status' => fake()->randomElement(["ativo","inativo"]),
            'observacoes' => fake()->text(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'portal_user_id_id' => User::factory(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
