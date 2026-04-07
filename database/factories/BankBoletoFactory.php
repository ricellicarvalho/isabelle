<?php

namespace Database\Factories;

use App\Models\BankRemessa;
use App\Models\Receivable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BankBoletoFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'receivable_id' => Receivable::factory(),
            'remessa_id' => fake()->word(),
            'nosso_numero' => fake()->word(),
            'numero_documento' => fake()->word(),
            'carteira' => fake()->regexify('[A-Za-z0-9]{10}'),
            'codigo_barras' => fake()->word(),
            'linha_digitavel' => fake()->word(),
            'data_vencimento' => fake()->date(),
            'valor' => fake()->randomFloat(2, 0, 99999999.99),
            'status' => fake()->randomElement(["pendente","emitido","pago","cancelado","baixado"]),
            'instrucao_remessa' => fake()->word(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'remessa_id_id' => BankRemessa::factory(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
