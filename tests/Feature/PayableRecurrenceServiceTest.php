<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\PayableRecurrenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayableRecurrenceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_independent_monthly_payable_for_each_month_and_preserves_month_end(): void
    {
        $user = User::factory()->create();
        $category = Category::create([
            'codigo' => '3',
            'descricao' => 'Despesas',
            'tipo' => 'despesa',
            'order' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);

        $payables = app(PayableRecurrenceService::class)->createMonthly([
            'category_id' => $category->id,
            'descricao' => 'Mensalidade do sistema',
            'valor' => 300,
            'data_vencimento' => '2026-08-31',
            'data_pagamento' => '2026-08-01',
            'valor_pago' => 300,
            'status' => 'pago',
            'created_by' => $user->id,
        ], '2026-12-31');

        $this->assertCount(5, $payables);
        $this->assertSame(
            ['2026-08-31', '2026-09-30', '2026-10-31', '2026-11-30', '2026-12-31'],
            $payables->map(fn ($payable): string => $payable->data_vencimento->toDateString())->all(),
        );
        $this->assertSame([1, 2, 3, 4, 5], $payables->pluck('recurrence_sequence')->all());
        $this->assertSame([5], $payables->pluck('recurrence_total')->unique()->values()->all());
        $this->assertSame(['pendente'], $payables->pluck('status')->unique()->values()->all());
        $this->assertTrue($payables->every(fn ($payable): bool => $payable->data_pagamento === null && $payable->valor_pago === null));
        $this->assertSame(1, $payables->pluck('payable_recurrence_id')->unique()->count());
        $this->assertDatabaseHas('payable_recurrences', [
            'occurrences_count' => 5,
            'frequency' => 'monthly',
        ]);
    }
}
