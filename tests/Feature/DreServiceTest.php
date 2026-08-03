<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\User;
use App\Services\DreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DreServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_payment_date_and_separates_current_and_previous_month_entries(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => '58.955.315/0001-72',
            'razao_social' => 'Cliente Teste',
            'created_by' => $user->id,
        ]);

        $entradas = $this->category($user, null, '1', 'Entradas', 'receita');
        $servicos = $this->category($user, $entradas->id, '1.1', 'Serviços', 'receita');
        $despesas = $this->category($user, null, '3', 'Despesas', 'despesa');
        $aluguel = $this->category($user, $despesas->id, '3.1', 'Aluguel', 'despesa');

        $this->receivable($user, $client, $servicos, '2026-08-10', '2026-08-15', 1000);
        $this->receivable($user, $client, $servicos, '2026-07-10', '2026-08-20', 500);
        $this->receivable($user, $client, $servicos, '2026-08-25', '2026-09-01', 999);

        Payable::create([
            'category_id' => $aluguel->id,
            'descricao' => 'Aluguel de julho pago em agosto',
            'valor' => 300,
            'valor_pago' => 300,
            'data_vencimento' => '2026-07-31',
            'data_pagamento' => '2026-08-05',
            'status' => 'pago',
            'created_by' => $user->id,
        ]);

        $report = DreService::generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));

        $this->assertSame(1000.0, $report['totais']['entradas_mes']);
        $this->assertSame(500.0, $report['totais']['entradas_periodos_anteriores']);
        $this->assertSame(1500.0, $report['totais']['receitas']);
        $this->assertSame(300.0, $report['totais']['despesas']);
        $this->assertSame(1200.0, $report['totais']['lucro_liquido']);
        $this->assertSame('3.1', $report['despesas'][0]['children'][0]['codigo']);
    }

    private function receivable(User $user, Client $client, Category $category, string $vencimento, string $pagamento, float $valor): void
    {
        Receivable::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'descricao' => 'Recebimento',
            'valor' => $valor,
            'valor_pago' => $valor,
            'data_vencimento' => $vencimento,
            'data_pagamento' => $pagamento,
            'status' => 'pago',
            'created_by' => $user->id,
        ]);
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
