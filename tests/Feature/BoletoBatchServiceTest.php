<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Category;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\User;
use App\Services\BoletoBatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class BoletoBatchServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_one_ordered_pdf_and_reuses_existing_boletos(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $this->bankAccount($user);

        $later = $this->receivable($user, $client, $category, 2, '2026-10-10');
        $earlier = $this->receivable($user, $client, $category, 1, '2026-09-10');

        $service = app(BoletoBatchService::class);
        $first = $service->generate(collect([$later, $earlier]));

        $this->assertStringStartsWith('%PDF', $first['pdf']);
        $this->assertSame(2, $first['created']);
        $this->assertSame(0, $first['reused']);
        $this->assertSame(
            ['2026-09-10', '2026-10-10'],
            $first['boletos']->map(fn ($boleto) => $boleto->data_vencimento->toDateString())->all(),
        );
        $this->assertSame(2, preg_match_all('/\/Type\s*\/Page(?!s)/', $first['pdf']));

        $second = $service->generate(collect([$earlier->fresh(), $later->fresh()]));

        $this->assertSame(0, $second['created']);
        $this->assertSame(2, $second['reused']);
        $this->assertSame(2, $client->receivables()->withCount('bankBoletos')->get()->sum('bank_boletos_count'));
    }

    public function test_it_rejects_receivables_from_different_clients_without_creating_boletos(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $this->bankAccount($user);
        $other = Client::create([
            'cnpj_cpf' => '98.765.432/0001-10',
            'razao_social' => 'Outro Cliente',
            'created_by' => $user->id,
        ]);

        $records = collect([
            $this->receivable($user, $client, $category, 1, '2026-09-10'),
            $this->receivable($user, $other, $category, 2, '2026-10-10'),
        ]);

        $this->assertSame(
            'Selecione parcelas de apenas um cliente.',
            app(BoletoBatchService::class)->validationMessage($records),
        );

        try {
            app(BoletoBatchService::class)->generate($records);
            $this->fail('A seleção de clientes diferentes deveria falhar.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Selecione parcelas de apenas um cliente.', $exception->getMessage());
        }

        $this->assertDatabaseCount('bank_boletos', 0);
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $client = Client::create([
            'cnpj_cpf' => '12.345.678/0001-90',
            'razao_social' => 'Cliente Teste',
            'endereco' => 'Rua Teste',
            'numero' => '10',
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

    private function bankAccount(User $user): BankAccount
    {
        return BankAccount::create([
            'banco' => '237',
            'descricao' => 'Bradesco Teste',
            'agencia' => '1234',
            'conta' => '567890',
            'conta_dv' => '1',
            'carteira' => '09',
            'cedente_nome' => 'Instituto Teste LTDA',
            'cedente_documento' => '00.000.000/0001-00',
            'cedente_endereco' => 'Rua Cedente, 100',
            'cedente_cidade_uf' => 'Palmas/TO',
            'proximo_nosso_numero' => 1,
            'ativo' => true,
            'created_by' => $user->id,
        ]);
    }

    private function receivable(User $user, Client $client, Category $category, int $installment, string $dueDate): Receivable
    {
        return Receivable::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'descricao' => "Parcela {$installment}",
            'valor' => 250,
            'data_vencimento' => $dueDate,
            'forma_pagamento' => 'boleto',
            'numero_parcela' => $installment,
            'status' => 'pendente',
            'created_by' => $user->id,
        ]);
    }
}
