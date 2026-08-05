<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Models\BankRemessa;
use App\Models\Category;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\User;
use App\Services\CnabRemessaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CnabRemessaServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_remessa_filename_is_short_and_readable(): void
    {
        $filename = CnabRemessaService::filename(5);

        $this->assertSame('REMESSA000005.REM', $filename);
        $this->assertLessThanOrEqual(20, strlen($filename));
        $this->assertMatchesRegularExpression('/^[A-Z0-9.]+$/', $filename);
    }

    public function test_reserve_sequencial_uses_existing_remessas_when_account_counter_is_behind(): void
    {
        $user = User::factory()->create();
        $account = $this->bankAccount($user, ['proximo_sequencial_remessa' => 2]);

        BankRemessa::create([
            'sequencial_arquivo' => 4,
            'data_geracao' => now(),
            'caminho_arquivo' => 'remessas/REMESSA000004.REM',
            'quantidade_titulos' => 1,
            'valor_total' => 100,
            'layout' => 'cnab400',
            'status' => 'gerado',
            'created_by' => $user->id,
        ]);

        $this->assertSame(5, $account->reserveSequencialRemessa());
        $this->assertSame(6, $account->fresh()->proximo_sequencial_remessa);
    }

    public function test_corrective_command_generates_remessa_with_explicit_sequence(): void
    {
        Storage::fake('local');

        [$user, $client, $category] = $this->dependencies();
        $this->bankAccount($user);

        $sourceOne = $this->remessa($user, 1);
        $sourceTwo = $this->remessa($user, 2);
        $boletoOne = $this->boleto($user, $client, $category, $sourceOne, 1, '2026-09-10', 250);
        $boletoTwo = $this->boleto($user, $client, $category, $sourceTwo, 2, '2026-10-10', 300);

        $this->artisan('bank-remessas:gerar-corretiva-bradesco', [
            '--sequencial' => 5,
            '--remessas' => '1,2',
            '--expected-count' => 2,
            '--force' => true,
        ])->assertSuccessful();

        $target = BankRemessa::query()->where('sequencial_arquivo', 5)->firstOrFail();

        $this->assertSame('remessas/REMESSA000005.REM', $target->caminho_arquivo);
        $this->assertSame(2, $target->quantidade_titulos);
        $this->assertSame('550.00', (string) $target->valor_total);
        $this->assertSame($target->id, $boletoOne->fresh()->remessa_id);
        $this->assertSame($target->id, $boletoTwo->fresh()->remessa_id);
        $this->assertSame(6, BankAccount::active()->proximo_sequencial_remessa);
        Storage::disk('local')->assertExists('remessas/REMESSA000005.REM');

        $this->artisan('bank-remessas:gerar-corretiva-bradesco', [
            '--sequencial' => 5,
            '--remessas' => '1,2',
            '--expected-count' => 2,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame(1, BankRemessa::query()->where('sequencial_arquivo', 5)->count());
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $client = Client::create([
            'cnpj_cpf' => '12.345.678/0001-90',
            'razao_social' => 'Cliente Teste',
            'endereco' => 'Rua Teste',
            'numero' => '10',
            'bairro' => 'Centro',
            'cep' => '77000-000',
            'cidade' => 'Palmas',
            'uf' => 'TO',
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

    private function bankAccount(User $user, array $attributes = []): BankAccount
    {
        return BankAccount::create(array_merge([
            'banco' => '237',
            'descricao' => 'Bradesco Teste',
            'agencia' => '1234',
            'conta' => '567890',
            'conta_dv' => '1',
            'carteira' => '09',
            'convenio' => '1234567',
            'cedente_nome' => 'Instituto Teste LTDA',
            'cedente_documento' => '00.000.000/0001-00',
            'cedente_endereco' => 'Rua Cedente, 100',
            'cedente_cidade_uf' => 'Palmas/TO',
            'layout_remessa' => '400',
            'proximo_nosso_numero' => 1,
            'proximo_sequencial_remessa' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ], $attributes));
    }

    private function remessa(User $user, int $sequencial): BankRemessa
    {
        return BankRemessa::create([
            'sequencial_arquivo' => $sequencial,
            'data_geracao' => now(),
            'caminho_arquivo' => sprintf('remessas/REMESSA%06d.REM', $sequencial),
            'quantidade_titulos' => 1,
            'valor_total' => 100,
            'layout' => 'cnab400',
            'status' => 'gerado',
            'created_by' => $user->id,
        ]);
    }

    private function boleto(
        User $user,
        Client $client,
        Category $category,
        BankRemessa $remessa,
        int $installment,
        string $dueDate,
        int $value
    ): BankBoleto {
        $receivable = Receivable::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'descricao' => "Parcela {$installment}",
            'valor' => $value,
            'data_vencimento' => $dueDate,
            'forma_pagamento' => 'boleto',
            'numero_parcela' => $installment,
            'status' => 'pendente',
            'created_by' => $user->id,
        ]);

        return BankBoleto::create([
            'receivable_id' => $receivable->id,
            'remessa_id' => $remessa->id,
            'nosso_numero' => str_pad((string) $installment, 11, '0', STR_PAD_LEFT),
            'numero_documento' => "DOC{$installment}",
            'carteira' => '09',
            'data_vencimento' => $dueDate,
            'valor' => $value,
            'status' => 'emitido',
            'created_by' => $user->id,
        ]);
    }
}
