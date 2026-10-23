<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contracts\ContractRenewalService;
use App\Services\ContractsReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ContractsReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_new_and_renewed_contracts_in_the_period(): void
    {
        [$user, $client, $category] = $this->dependencies();

        Carbon::setTestNow('2026-08-10 09:00:00');
        $renewedContract = $this->contract($user, $client, $category, 'CT-ANTIGO');

        Carbon::setTestNow('2026-09-05 10:00:00');
        $newContract = $this->contract($user, $client, $category, 'CT-NOVO');

        Carbon::setTestNow('2026-09-20 11:00:00');
        app(ContractRenewalService::class)->renew($renewedContract, [
            'client_id' => $client->id,
            'category_id' => $category->id,
            'numero' => 'CT-ANTIGO',
            'tipo_servico' => 'consultoria',
            'descricao' => 'Contrato renovado',
            'valor_total' => 1800,
            'forma_pagamento' => 'pix',
            'quantidade_parcelas' => 3,
            'data_inicio' => '2027-01-01',
            'data_fim' => '2027-12-31',
            'arquivo_pdf' => null,
            'observacoes' => null,
            'change_reason' => 'Renovação anual.',
        ], $user->id);

        $report = ContractsReportService::generate([
            'data_inicio' => '2026-09-01 08:30:00',
            'data_fim' => '2026-09-30 08:30:00',
            'movement_type' => 'all',
        ]);

        $this->assertSame(2, $report['count']);
        $this->assertSame(1, $report['new_count']);
        $this->assertSame(1, $report['renewal_count']);
        $this->assertSame(['CT-ANTIGO', 'CT-NOVO'], array_column($report['items'], 'numero'));
        $this->assertSame(['renewal', 'new'], array_column($report['items'], 'movement_type'));
        $this->assertSame($newContract->id, $report['items'][1]['contract_id']);
    }

    public function test_it_filters_by_movement_type_and_client(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $otherClient = Client::create([
            'tipo_pessoa' => 'pj',
            'cnpj_cpf' => '11.222.333/0001-81',
            'razao_social' => 'Outro Cliente',
            'created_by' => $user->id,
        ]);

        Carbon::setTestNow('2026-09-10 09:00:00');
        $this->contract($user, $client, $category, 'CT-CLIENTE');
        $this->contract($user, $otherClient, $category, 'CT-OUTRO');

        $report = ContractsReportService::generate([
            'client_id' => $client->id,
            'data_inicio' => '2026-09-01',
            'data_fim' => '2026-09-30',
            'movement_type' => 'new',
        ]);

        $this->assertSame(1, $report['count']);
        $this->assertSame('CT-CLIENTE', $report['items'][0]['numero']);
        $this->assertSame('Cliente Teste', $report['filters']['cliente']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function dependencies(): array
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

        return [$user, $client, $category];
    }

    private function contract(User $user, Client $client, Category $category, string $number): Contract
    {
        return Contract::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'numero' => $number,
            'tipo_servico' => 'consultoria',
            'descricao' => 'Contrato de teste',
            'valor_total' => 1200,
            'forma_pagamento' => 'pix',
            'quantidade_parcelas' => 2,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'status' => 'ativo',
            'created_by' => $user->id,
        ]);
    }
}
