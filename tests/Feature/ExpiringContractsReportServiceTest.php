<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Services\ExpiringContractsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ExpiringContractsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Client $client;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-09-09 10:00:00');

        $this->user = User::factory()->create();
        $this->client = Client::create([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => '58.955.315/0001-72',
            'razao_social' => 'Cliente Teste',
            'created_by' => $this->user->id,
        ]);
        $this->category = Category::create([
            'codigo' => '1',
            'descricao' => 'Receitas',
            'tipo' => 'receita',
            'order' => 1,
            'ativo' => true,
            'created_by' => $this->user->id,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_default_report_includes_only_contracts_due_within_thirty_days(): void
    {
        $this->contract('VENCIDO-FINALIZADO', '2026-09-01', 'finalizado');
        $this->contract('VENCIDO-ATIVO', '2026-09-08', 'ativo');
        $this->contract('VENCE-HOJE', '2026-09-09', 'ativo');
        $this->contract('A-VENCER', '2026-10-09', 'ativo');
        $this->contract('FORA-DO-PRAZO', '2026-10-10', 'ativo');
        $this->contract('CANCELADO', '2026-09-05', 'cancelado');

        $items = ExpiringContractsReportService::generate([])->all();

        $this->assertSame(
            ['VENCE-HOJE', 'A-VENCER'],
            array_column($items, 'numero'),
        );
        $this->assertSame('Vence hoje', $items[0]['situacao_prazo']);
        $this->assertSame('Vence em 30 dias', $items[1]['situacao_prazo']);
    }

    public function test_it_can_filter_only_expired_or_only_upcoming_contracts(): void
    {
        $this->contract('VENCIDO', '2026-09-01', 'finalizado');
        $this->contract('HOJE', '2026-09-09', 'ativo');
        $this->contract('FUTURO', '2026-09-16', 'ativo');

        $expired = ExpiringContractsReportService::generate(['situacao' => 'vencidos']);
        $upcoming = ExpiringContractsReportService::generate(['situacao' => 'a_vencer', 'prazo' => '7']);

        $this->assertSame(['VENCIDO'], $expired->pluck('numero')->all());
        $this->assertSame(['HOJE', 'FUTURO'], $upcoming->pluck('numero')->all());
    }

    public function test_custom_period_can_retrieve_expired_contracts(): void
    {
        $this->contract('AGOSTO', '2026-08-31', 'finalizado');
        $this->contract('PRIMEIRO-DO-MES', '2026-09-01', 'finalizado');
        $this->contract('DEPOIS-DO-PERIODO', '2026-09-06', 'finalizado');

        $items = ExpiringContractsReportService::generate([
            'situacao' => 'todos',
            'data_inicio' => '2026-09-01',
            'data_fim' => '2026-09-05',
        ]);

        $this->assertSame(['PRIMEIRO-DO-MES'], $items->pluck('numero')->all());
    }

    private function contract(string $numero, string $dataFim, string $status): Contract
    {
        return Contract::create([
            'client_id' => $this->client->id,
            'category_id' => $this->category->id,
            'numero' => $numero,
            'tipo_servico' => 'consultoria',
            'valor_total' => 1000,
            'forma_pagamento' => 'pix',
            'quantidade_parcelas' => 1,
            'data_inicio' => '2026-01-01',
            'data_fim' => $dataFim,
            'status' => $status,
            'created_by' => $this->user->id,
        ]);
    }
}
