<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryCodeGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_the_next_code_at_each_tree_level_without_reusing_deleted_codes(): void
    {
        $user = User::factory()->create();
        $root = $this->category($user, null, '3', 'Despesas', 'despesa');
        $this->category($user, $root->id, '3.1', 'Aluguel', 'despesa');
        $deleted = $this->category($user, $root->id, '3.4', 'Administrativas', 'despesa');
        $deleted->delete();

        $generator = app(CategoryCodeGenerator::class);

        $this->assertSame('4', $generator->next());
        $this->assertSame('3.5', $generator->next($root->id));
    }

    private function category(User $user, ?int $parentId, string $codigo, string $descricao, string $tipo): Category
    {
        return Category::create([
            'parent_id' => $parentId,
            'codigo' => $codigo,
            'descricao' => $descricao,
            'tipo' => $tipo,
            'order' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);
    }
}
