<?php

namespace Database\Factories;

use App\Models\;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'category_id' => ::factory(),
            'numero' => fake()->word(),
            'tipo_servico' => fake()->randomElement(["nr1","palestra","consultoria","treinamento","outro"]),
            'descricao' => fake()->text(),
            'valor_total' => fake()->randomFloat(2, 0, 99999999.99),
            'forma_pagamento' => fake()->randomElement(["boleto","pix","transferencia","dinheiro","cartao"]),
            'quantidade_parcelas' => fake()->randomNumber(),
            'data_inicio' => fake()->date(),
            'data_fim' => fake()->date(),
            'status' => fake()->randomElement(["rascunho","ativo","finalizado","cancelado"]),
            'arquivo_pdf' => fake()->word(),
            'observacoes' => fake()->text(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
