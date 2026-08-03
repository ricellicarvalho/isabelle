<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Payable;
use App\Models\Receivable;
use App\Models\User;
use App\Services\DashboardFinanceService;
use App\Services\DreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DashboardFinanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_realized_values_match_dre_and_use_payment_date(): void
    {
        Carbon::setTestNow('2026-08-15 10:00:00');

        $user = User::factory()->create();
        $client = Client::create([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => '58.955.315/0001-72',
            'razao_social' => 'Cliente Dashboard',
            'created_by' => $user->id,
        ]);
        $revenue = $this->category($user, '1', 'Receitas', 'receita');
        $expense = $this->category($user, '3', 'Despesas', 'despesa');

        $this->receivable($user, $client, $revenue, 1000, '2026-07-10', '2026-08-05', 'pago');
        $this->receivable($user, $client, $revenue, 900, '2026-08-10', '2026-07-31', 'pago');
        $this->receivable($user, $client, $revenue, 700, '2026-08-14', null, 'vencido');
        $this->receivable($user, $client, $revenue, 200, '2026-08-15', null, 'pendente');

        $this->payable($user, $expense, 300, '2026-07-31', '2026-08-08', 'pago');
        $this->payable($user, $expense, 400, '2026-08-14', null, 'vencido');
        $this->payable($user, $expense, 100, '2026-08-15', null, 'pendente');

        $inicio = Carbon::parse('2026-08-01');
        $fim = Carbon::parse('2026-08-31');
        $summary = app(DashboardFinanceService::class)->summary($inicio, $fim);
        $dre = DreService::generate($inicio, $fim)['totais'];

        $this->assertSame($dre['receitas'], $summary['receitas']);
        $this->assertSame($dre['custos'] + $dre['despesas'], $summary['saidas']);
        $this->assertSame($dre['lucro_liquido'], $summary['resultado']);
        $this->assertSame(1000.0, $summary['receitas']);
        $this->assertSame(300.0, $summary['saidas']);
        $this->assertSame(['total' => 700.0, 'count' => 1], $summary['receber_vencidos']);
        $this->assertSame(['total' => 200.0, 'count' => 1], $summary['receber_hoje']);
        $this->assertSame(['total' => 400.0, 'count' => 1], $summary['pagar_vencidos']);
        $this->assertSame(['total' => 100.0, 'count' => 1], $summary['pagar_hoje']);

        Carbon::setTestNow();
    }

    private function category(User $user, string $code, string $description, string $type): Category
    {
        return Category::create([
            'codigo' => $code,
            'descricao' => $description,
            'tipo' => $type,
            'order' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);
    }

    private function receivable(User $user, Client $client, Category $category, float $value, string $dueDate, ?string $paymentDate, string $status): void
    {
        Receivable::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'descricao' => 'Conta a receber',
            'valor' => $value,
            'valor_pago' => $paymentDate ? $value : null,
            'data_vencimento' => $dueDate,
            'data_pagamento' => $paymentDate,
            'status' => $status,
            'created_by' => $user->id,
        ]);
    }

    private function payable(User $user, Category $category, float $value, string $dueDate, ?string $paymentDate, string $status): void
    {
        Payable::create([
            'category_id' => $category->id,
            'descricao' => 'Conta a pagar',
            'valor' => $value,
            'valor_pago' => $paymentDate ? $value : null,
            'data_vencimento' => $dueDate,
            'data_pagamento' => $paymentDate,
            'status' => $status,
            'created_by' => $user->id,
        ]);
    }
}
