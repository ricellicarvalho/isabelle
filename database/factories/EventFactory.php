<?php

namespace Database\Factories;

use App\Models\;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'client_id' => Client::factory(),
            'contract_id' => ::factory(),
            'user_id' => ::factory(),
            'titulo' => fake()->word(),
            'descricao' => fake()->text(),
            'tipo' => fake()->randomElement(["avaliacao_nr1","devolutiva","treinamento","palestra","reuniao","outro"]),
            'data_inicio' => fake()->dateTime(),
            'data_fim' => fake()->dateTime(),
            'dia_inteiro' => fake()->boolean(),
            'local' => fake()->word(),
            'status' => fake()->randomElement(["agendado","realizado","cancelado"]),
            'bloquear_agenda' => fake()->boolean(),
            'cor' => fake()->regexify('[A-Za-z0-9]{7}'),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
