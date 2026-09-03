<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\CategoryCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_a_category_cannot_be_its_own_parent(): void
    {
        $user = User::factory()->create();
        $category = $this->category($user, null, '1', 'Receitas', 'receita');

        $this->expectException(ValidationException::class);

        $category->update(['parent_id' => $category->id]);
    }

    public function test_a_category_cannot_be_moved_below_one_of_its_descendants(): void
    {
        $user = User::factory()->create();
        $root = $this->category($user, null, '1', 'Receitas', 'receita');
        $child = $this->category($user, $root->id, '1.1', 'Consultoria', 'receita');

        $this->expectException(ValidationException::class);

        $root->update(['parent_id' => $child->id]);
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
