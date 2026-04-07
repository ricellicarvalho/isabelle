<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PricingFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'nome' => fake()->word(),
            'descricao' => fake()->text(),
            'custo_direto' => fake()->randomFloat(2, 0, 99999999.99),
            'custo_indireto' => fake()->randomFloat(2, 0, 99999999.99),
            'margem_lucro' => fake()->randomFloat(2, 0, 999.99),
            'preco_venda' => fake()->randomFloat(2, 0, 99999999.99),
            'observacoes' => fake()->text(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
