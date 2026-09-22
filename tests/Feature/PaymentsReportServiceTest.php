<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Payable;
use App\Models\User;
use App\Services\PaymentsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_unpaid_payables_by_due_date_and_keeps_the_order(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'codigo' => '1',
            'descricao' => 'Despesas',
            'tipo' => 'despesa',
            'order' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);

        $this->payable($user, $category, 'Pendente antigo', '2026-08-05', null, 100);
        $this->payable($user, $category, 'Pendente recente', '2026-08-25', null, 150);
        $this->payable($user, $category, 'Fora do período', '2026-09-01', null, 200);
        $this->payable($user, $category, 'Já pago', '2026-08-15', '2026-08-20', 300);

        $report = PaymentsReportService::generate([
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-31',
            'situacao' => 'nao_pago',
        ]);

        $this->assertSame(['Pendente recente', 'Pendente antigo'], array_column($report['items'], 'descricao'));
        $this->assertSame(['25/08/2026', '05/08/2026'], array_column($report['items'], 'data'));
        $this->assertSame(250.0, $report['total']);
        $this->assertSame(2, $report['count']);
    }

    private function payable(
        User $user,
        Category $category,
        string $descricao,
        string $vencimento,
        ?string $pagamento,
        float $valor,
    ): void {
        Payable::create([
            'category_id' => $category->id,
            'fornecedor' => 'Fornecedor Teste',
            'descricao' => $descricao,
            'valor' => $valor,
            'valor_pago' => $pagamento ? $valor : null,
            'data_vencimento' => $vencimento,
            'data_pagamento' => $pagamento,
            'forma_pagamento' => $pagamento ? 'pix' : null,
            'status' => $pagamento ? 'pago' : 'pendente',
            'created_by' => $user->id,
        ]);
    }
}
