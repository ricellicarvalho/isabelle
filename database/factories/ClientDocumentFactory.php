<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'titulo' => fake()->word(),
            'tipo' => fake()->randomElement(["laudo","foto","relatorio","matriz_risco","certificado","outro"]),
            'caminho_arquivo' => fake()->word(),
            'descricao' => fake()->text(),
            'visivel_portal' => fake()->boolean(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
