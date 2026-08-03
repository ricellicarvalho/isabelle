<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Client;
use App\Models\Contract;
use App\Models\User;
use App\Services\Contracts\ContractCorrectionService;
use App\Services\Contracts\ContractRenewalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ContractRenewalTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_initial_version_for_a_new_contract(): void
    {
        [$user, $client, $category] = $this->dependencies();

        $contract = $this->contract($user, $client, $category);

        $this->assertNotNull($contract->fresh()->current_version_id);
        $this->assertDatabaseHas('contract_versions', [
            'contract_id' => $contract->id,
            'version_number' => 1,
            'change_type' => 'original',
            'valor_total' => 1200,
        ]);
        $this->assertSame(2, $contract->receivables()->count());
        $this->assertSame(0, $contract->receivables()->whereNull('contract_version_id')->count());
    }

    public function test_it_renews_without_overwriting_the_previous_version_or_receivables(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category);
        $originalVersionId = $contract->fresh()->current_version_id;
        $originalReceivableIds = $contract->receivables()->pluck('id')->all();

        $version = app(ContractRenewalService::class)->renew($contract, [
            'client_id' => $client->id,
            'category_id' => $category->id,
            'numero' => 'CT-TESTE',
            'tipo_servico' => 'consultoria',
            'descricao' => 'Serviço renovado',
            'valor_total' => 1800,
            'forma_pagamento' => 'pix',
            'quantidade_parcelas' => 3,
            'data_inicio' => '2027-01-01',
            'data_fim' => '2027-12-31',
            'arquivo_pdf' => null,
            'observacoes' => 'Nova vigência',
            'change_reason' => 'Renovação anual aprovada.',
        ], $user->id);

        $this->assertSame(2, $version->version_number);
        $this->assertDatabaseHas('contract_versions', [
            'id' => $originalVersionId,
            'status' => 'superseded',
            'valor_total' => 1200,
        ]);
        $this->assertSame('2026-12-31', $contract->versions()->find($originalVersionId)->data_fim->toDateString());
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'current_version_id' => $version->id,
            'valor_total' => 1800,
            'status' => 'ativo',
        ]);
        $this->assertSame(5, $contract->receivables()->count());
        $this->assertSame($originalReceivableIds, $contract->receivables()->where('contract_version_id', $originalVersionId)->pluck('id')->all());
        $this->assertSame(3, $contract->receivables()->where('contract_version_id', $version->id)->count());
        $this->assertDatabaseHas('contract_version_changes', [
            'contract_version_id' => $version->id,
            'field' => 'valor_total',
            'old_value' => '1200.00',
            'new_value' => '1800.00',
        ]);
    }

    public function test_it_blocks_direct_changes_to_versioned_fields_of_an_active_contract(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category);

        $this->expectException(ValidationException::class);
        $contract->update(['valor_total' => 9999]);
    }

    public function test_cancelling_a_contract_marks_its_current_version_and_pending_installments(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category);

        $contract->update(['status' => 'cancelado']);

        $this->assertSame('cancelled', $contract->fresh()->currentVersion->status);
        $this->assertSame(2, $contract->receivables()->where('status', 'cancelado')->count());
    }

    public function test_reason_is_optional_but_identification_fields_cannot_change(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category);

        $data = [
            'client_id' => $client->id,
            'category_id' => $category->id,
            'numero' => 'CT-TESTE',
            'tipo_servico' => 'consultoria',
            'descricao' => 'Renovação sem motivo',
            'valor_total' => 1500,
            'forma_pagamento' => 'pix',
            'quantidade_parcelas' => 3,
            'data_inicio' => '2027-01-01',
            'data_fim' => '2027-12-31',
            'arquivo_pdf' => null,
            'observacoes' => null,
            'change_reason' => null,
        ];

        $version = app(ContractRenewalService::class)->renew($contract, $data, $user->id);
        $this->assertNull($version->change_reason);

        $data['numero'] = 'OUTRO-CONTRATO';
        $this->expectException(ValidationException::class);
        app(ContractRenewalService::class)->renew($contract->fresh(), $data, $user->id);
    }

    public function test_it_corrects_the_current_version_and_pending_installments_without_renewing(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category)->fresh();
        $previousVersionId = $contract->current_version_id;
        $receivableIds = $contract->receivables()->orderBy('numero_parcela')->pluck('id')->all();

        $version = app(ContractCorrectionService::class)->correct($contract, [
            'data_inicio' => '2026-09-01',
            'data_fim' => '2027-08-31',
            'observacoes' => 'Vigência corrigida.',
            'change_reason' => 'A data original foi informada incorretamente.',
        ], $user->id);

        $this->assertSame('correction', $version->change_type);
        $this->assertSame(2, $version->version_number);
        $this->assertSame('superseded', $contract->versions()->find($previousVersionId)->status);
        $this->assertSame($receivableIds, $contract->receivables()->orderBy('numero_parcela')->pluck('id')->all());
        $this->assertSame(
            ['2026-09-01', '2026-10-01'],
            $contract->receivables()->orderBy('numero_parcela')->get()->map(fn ($item): string => $item->data_vencimento->toDateString())->all(),
        );
        $this->assertSame([$version->id], $contract->receivables()->pluck('contract_version_id')->unique()->values()->all());
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'current_version_id' => $version->id,
            'observacoes' => 'Vigência corrigida.',
        ]);
        $correctedContract = $contract->fresh();
        $this->assertSame('2026-09-01', $correctedContract->data_inicio->toDateString());
        $this->assertSame('2027-08-31', $correctedContract->data_fim->toDateString());
        $this->assertDatabaseHas('contract_version_changes', [
            'contract_version_id' => $version->id,
            'field' => 'data_inicio',
            'old_value' => '2026-01-01',
            'new_value' => '2026-09-01',
        ]);
    }

    public function test_it_blocks_date_correction_when_the_current_version_has_a_paid_installment(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $contract = $this->contract($user, $client, $category)->fresh();
        $contract->receivables()->first()->update([
            'status' => 'pago',
            'data_pagamento' => now(),
            'valor_pago' => 600,
        ]);

        $this->expectException(ValidationException::class);

        app(ContractCorrectionService::class)->correct($contract, [
            'data_inicio' => '2026-09-01',
            'data_fim' => '2027-08-31',
            'observacoes' => null,
            'change_reason' => 'Correção de teste.',
        ], $user->id);
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $client = Client::create([
            'cnpj_cpf' => '12.345.678/0001-90',
            'razao_social' => 'Cliente Teste',
            'created_by' => $user->id,
        ]);
        $category = Category::create([
            'codigo' => '1.1',
            'descricao' => 'Receitas de serviços',
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
            'numero' => 'CT-TESTE',
            'tipo_servico' => 'consultoria',
            'descricao' => 'Serviço original',
            'valor_total' => 1200,
            'forma_pagamento' => 'boleto',
            'quantidade_parcelas' => 2,
            'data_inicio' => '2026-01-01',
            'data_fim' => '2026-12-31',
            'status' => 'ativo',
            'created_by' => $user->id,
        ]);
    }
}
