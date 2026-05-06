<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Admin
        $admin = User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@isabelle.com.br',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $by = $admin->id;

        // Plano de Contas padrão
        $receitas = Category::create([
            'codigo' => '1',
            'descricao' => 'Receitas',
            'tipo' => 'receita',
            'order' => 1,
            'created_by' => $by,
        ]);

        Category::create(['parent_id' => $receitas->id, 'codigo' => '1.1', 'descricao' => 'Consultoria NR-1', 'tipo' => 'receita', 'order' => 1, 'created_by' => $by]);
        Category::create(['parent_id' => $receitas->id, 'codigo' => '1.2', 'descricao' => 'Palestras', 'tipo' => 'receita', 'order' => 2, 'created_by' => $by]);
        Category::create(['parent_id' => $receitas->id, 'codigo' => '1.3', 'descricao' => 'Treinamentos', 'tipo' => 'receita', 'order' => 3, 'created_by' => $by]);

        $custos = Category::create([
            'codigo' => '2',
            'descricao' => 'Custos',
            'tipo' => 'custo',
            'order' => 2,
            'created_by' => $by,
        ]);

        Category::create(['parent_id' => $custos->id, 'codigo' => '2.1', 'descricao' => 'Mão de Obra Direta', 'tipo' => 'custo', 'order' => 1, 'created_by' => $by]);
        Category::create(['parent_id' => $custos->id, 'codigo' => '2.2', 'descricao' => 'Material de Trabalho', 'tipo' => 'custo', 'order' => 2, 'created_by' => $by]);

        $despesas = Category::create([
            'codigo' => '3',
            'descricao' => 'Despesas',
            'tipo' => 'despesa',
            'order' => 3,
            'created_by' => $by,
        ]);

        Category::create(['parent_id' => $despesas->id, 'codigo' => '3.1', 'descricao' => 'Aluguel', 'tipo' => 'despesa', 'order' => 1, 'created_by' => $by]);
        Category::create(['parent_id' => $despesas->id, 'codigo' => '3.2', 'descricao' => 'Energia Elétrica', 'tipo' => 'despesa', 'order' => 2, 'created_by' => $by]);
        Category::create(['parent_id' => $despesas->id, 'codigo' => '3.3', 'descricao' => 'Internet e Telefonia', 'tipo' => 'despesa', 'order' => 3, 'created_by' => $by]);
        Category::create(['parent_id' => $despesas->id, 'codigo' => '3.4', 'descricao' => 'Despesas Administrativas', 'tipo' => 'despesa', 'order' => 4, 'created_by' => $by]);

        // Roles e permissões
        $this->call(RoleSeeder::class);

        // Atribui super_admin ao usuário administrador
        $admin->assignRole('super_admin');

        // Demo data (clientes, contratos, fornecedores, notificações)
        $this->call(DemoDataSeeder::class);

        // Conta bancária de exemplo (Bradesco - carteira 09)
        BankAccount::firstOrCreate(['banco' => '237'], [
            'banco' => '237',
            'descricao' => 'Bradesco - Cobrança Registrada',
            'agencia' => '1234',
            'conta' => '0567890',
            'conta_dv' => '1',
            'carteira' => '09',
            'convenio' => '0567890',
            'cedente_nome' => 'Instituto Alves Neves LTDA',
            'cedente_documento' => '00.000.000/0001-00',
            'cedente_endereco' => 'Rua Exemplo, 100',
            'cedente_cidade_uf' => 'São Paulo/SP',
            'layout_remessa' => '400',
            'proximo_nosso_numero' => 1,
            'proximo_sequencial_remessa' => 1,
            'ativo' => true,
            'created_by' => $by,
        ]);
    }
}
