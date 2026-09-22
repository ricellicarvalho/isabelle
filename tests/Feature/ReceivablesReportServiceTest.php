<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\User;
use App\Services\ReceivablesReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceivablesReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_and_orders_receivables_by_payment_date(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => '58.955.315/0001-72',
            'razao_social' => 'Cliente Teste',
            'created_by' => $user->id,
        ]);
        $category = Category::create([
            'codigo' => '1',
            'descricao' => 'Receitas',
            'tipo' => 'receita',
            'order' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);

        $this->receivable($user, $client, $category, 'Recebimento antigo', '2026-09-05', '2026-08-20', 100, 'pix');
        $this->receivable($user, $client, $category, 'Recebimento recente', '2026-08-01', '2026-08-25', 150, 'pix');
        $this->receivable($user, $client, $category, 'Outra forma', '2026-08-10', '2026-08-22', 200, 'boleto');
        $this->receivable($user, $client, $category, 'Ainda pendente', '2026-08-15', null, 300, null);

        $report = ReceivablesReportService::generate([
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-31',
            'forma_pagamento' => 'pix',
        ]);

        $this->assertSame(['Recebimento recente', 'Recebimento antigo'], array_column($report['items'], 'descricao'));
        $this->assertSame(['25/08/2026', '20/08/2026'], array_column($report['items'], 'data'));
        $this->assertSame(250.0, $report['total']);
        $this->assertSame(2, $report['count']);
    }

    public function test_it_filters_unpaid_receivables_by_due_date_and_keeps_the_order(): void
    {
        $user = User::factory()->create();
        $client = Client::create([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => '58.955.315/0001-72',
            'razao_social' => 'Cliente Teste',
            'created_by' => $user->id,
        ]);
        $category = Category::create([
            'codigo' => '1',
            'descricao' => 'Receitas',
            'tipo' => 'receita',
            'order' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);

        $this->receivable($user, $client, $category, 'Pendente antigo', '2026-08-05', null, 100, null);
        $this->receivable($user, $client, $category, 'Pendente recente', '2026-08-25', null, 150, null);
        $this->receivable($user, $client, $category, 'Fora do período', '2026-09-01', null, 200, null);
        $this->receivable($user, $client, $category, 'Já recebido', '2026-08-15', '2026-08-20', 300, 'pix');

        $report = ReceivablesReportService::generate([
            'data_inicio' => '2026-08-01',
            'data_fim' => '2026-08-31',
            'situacao' => 'nao_pago',
        ]);

        $this->assertSame(['Pendente recente', 'Pendente antigo'], array_column($report['items'], 'descricao'));
        $this->assertSame(['25/08/2026', '05/08/2026'], array_column($report['items'], 'data'));
        $this->assertSame(250.0, $report['total']);
        $this->assertSame(2, $report['count']);
    }

    private function receivable(
        User $user,
        Client $client,
        Category $category,
        string $descricao,
        string $vencimento,
        ?string $pagamento,
        float $valor,
        ?string $formaPagamento,
    ): void {
        Receivable::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'descricao' => $descricao,
            'valor' => $valor,
            'valor_pago' => $pagamento ? $valor : null,
            'data_vencimento' => $vencimento,
            'data_pagamento' => $pagamento,
            'forma_pagamento' => $formaPagamento,
            'status' => $pagamento ? 'pago' : 'pendente',
            'created_by' => $user->id,
        ]);
    }
}
