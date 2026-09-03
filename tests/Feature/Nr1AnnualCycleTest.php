<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Nr1Cycle;
use App\Models\User;
use App\Services\Contracts\ContractRenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Nr1AnnualCycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_nr1_contract_opens_one_cycle_for_its_initial_year(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category);

        $this->assertDatabaseHas('nr1_cycles', [
            'client_id' => $client->id,
            'contract_id' => $contract->id,
            'opened_by_contract_version_id' => $contract->fresh()->current_version_id,
            'reference_year' => 2026,
            'status' => 'pendente',
        ]);
        $this->assertSame(1, $client->nr1Cycles()->count());
    }

    public function test_renewing_an_nr1_contract_opens_a_new_cycle_without_changing_the_old_one(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category)->fresh();
        $firstCycleId = $contract->nr1Cycles()->firstOrFail()->id;

        $version = app(ContractRenewalService::class)->renew($contract, [
            'client_id' => $client->id,
            'category_id' => $category->id,
            'numero' => $contract->numero,
            'tipo_servico' => 'nr1',
            'descricao' => 'NR-1 renovada',
            'valor_total' => 2400,
            'forma_pagamento' => 'pix',
            'quantidade_parcelas' => 2,
            'data_inicio' => '2027-01-01',
            'data_fim' => '2027-12-31',
            'arquivo_pdf' => null,
            'observacoes' => null,
            'change_reason' => 'Renovação anual.',
        ], $user->id);

        $this->assertSame(2, $contract->nr1Cycles()->count());
        $this->assertDatabaseHas('nr1_cycles', ['id' => $firstCycleId, 'reference_year' => 2026]);
        $this->assertDatabaseHas('nr1_cycles', [
            'opened_by_contract_version_id' => $version->id,
            'reference_year' => 2027,
        ]);
    }

    public function test_cycle_status_is_calculated_without_modifying_the_legacy_client_copy(): void
    {
        [$user, $client] = $this->dependencies();
        $legacyChecklist = ['etapa1' => true];
        $client->update(['nr1_checklist' => $legacyChecklist]);

        $cycle = Nr1Cycle::create([
            'client_id' => $client->id,
            'reference_year' => 2026,
            'checklist' => array_fill_keys(['etapa1', 'etapa2', 'etapa3', 'etapa4', 'etapa5'], true),
            'created_by' => $user->id,
        ]);

        $this->assertSame('finalizada', $cycle->status);
        $this->assertSame($legacyChecklist, $client->fresh()->nr1_checklist);
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $client = Client::create([
            'cnpj_cpf' => '98.765.432/0001-10',
            'razao_social' => 'Cliente NR-1',
            'created_by' => $user->id,
        ]);
        $category = Category::create([
            'codigo' => '1.1-NR1',
            'descricao' => 'Consultoria NR-1',
            'tipo' => 'receita',
            'created_by' => $user->id,
        ]);

        return [$user, $client, $category];
    }

    private function contract(User $user, Client $client, Category $category): Contract
    {
        return Contract::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'numero' => 'CT-NR1-TESTE',
            'tipo_servico' => 'nr1',
            'descricao' => 'Serviço NR-1',
            'valor_total' => 1200,
            'forma_pagamento' => 'boleto',
            'quantidade_parcelas' => 1,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'status' => 'ativo',
            'created_by' => $user->id,
        ]);
    }
}
