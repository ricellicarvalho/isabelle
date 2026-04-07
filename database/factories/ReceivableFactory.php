<?php

namespace Database\Factories;

use App\Models\;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceivableFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'contract_id' => ::factory(),
            'category_id' => ::factory(),
            'descricao' => fake()->word(),
            'valor' => fake()->randomFloat(2, 0, 99999999.99),
            'data_vencimento' => fake()->date(),
            'data_pagamento' => fake()->date(),
            'valor_pago' => fake()->randomFloat(2, 0, 99999999.99),
            'forma_pagamento' => fake()->randomElement(["boleto","pix","transferencia","dinheiro","cartao"]),
            'numero_parcela' => fake()->randomNumber(),
            'status' => fake()->randomElement(["pendente","pago","cancelado","vencido"]),
            'observacoes' => fake()->text(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
