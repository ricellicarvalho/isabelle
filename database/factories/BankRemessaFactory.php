<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankRemessaFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'sequencial_arquivo' => fake()->randomNumber(),
            'data_geracao' => fake()->dateTime(),
            'caminho_arquivo' => fake()->word(),
            'quantidade_titulos' => fake()->randomNumber(),
            'valor_total' => fake()->randomFloat(2, 0, 9999999999.99),
            'layout' => fake()->randomElement(["cnab240","cnab400"]),
            'status' => fake()->randomElement(["gerado","enviado","processado"]),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
