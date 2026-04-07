<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'parent_id' => fake()->word(),
            'codigo' => fake()->word(),
            'descricao' => fake()->word(),
            'tipo' => fake()->randomElement(["receita","custo","despesa"]),
            'order' => fake()->randomNumber(),
            'ativo' => fake()->boolean(),
            'created_by' => fake()->word(),
            'deleted_by' => fake()->word(),
            'parent_id_id' => Category::factory(),
            'created_by_id' => User::factory(),
            'deleted_by_id' => User::factory(),
        ];
    }
}
