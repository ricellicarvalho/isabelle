<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Models\Category;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\User;
use App\Services\BankBoletoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankBoletoBarcodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_bradesco_barcode_general_digit_uses_standard_modulo_11_weights(): void
    {
        [$user, $client, $category] = $this->dependencies();
        $this->bankAccount($user);

        $receivable = Receivable::create([
            'client_id' => $client->id,
            'category_id' => $category->id,
            'descricao' => 'Parcela teste',
            'valor' => 228.50,
            'data_vencimento' => '2026-08-10',
            'forma_pagamento' => 'boleto',
            'numero_parcela' => 1,
            'status' => 'pendente',
            'created_by' => $user->id,
        ]);

        $boleto = BankBoleto::create([
            'receivable_id' => $receivable->id,
            'nosso_numero' => '00000000036',
            'numero_documento' => '259',
            'carteira' => '09',
            'data_vencimento' => '2026-08-10',
            'valor' => 228.50,
            'status' => 'emitido',
            'created_by' => $user->id,
        ]);

        BankBoletoService::buildLibBoleto($boleto);
        $boleto->refresh();

        $this->assertSame('00000000036', $boleto->nosso_numero);
        $this->assertSame('23795153400000228500590090000000003600889420', $boleto->codigo_barras);
        $this->assertSame('23790.59005 90000.000001 36008.894200 5 15340000022850', $boleto->linha_digitavel);
    }

    private function dependencies(): array
    {
        $user = User::factory()->create();
        $client = Client::create([
            'cnpj_cpf' => '12.547.355/0001-84',
            'razao_social' => 'Cerrados Florestal Ltda',
            'endereco' => 'Avenida Paraná',
            'numero' => '1676',
            'bairro' => 'Setor Central',
            'cep' => '77403-050',
            'cidade' => 'Gurupi',
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
            'descricao' => 'Bradesco',
            'agencia' => '0590',
            'conta' => '88942',
            'conta_dv' => '3',
            'carteira' => '09',
            'convenio' => '6074473',
            'cedente_nome' => 'ALVES E NEVES LTDA',
            'cedente_documento' => '58.955.315/0001-72',
            'cedente_endereco' => 'Endereco',
            'cedente_cidade_uf' => 'Gurupi/TO',
            'layout_remessa' => '400',
            'proximo_nosso_numero' => 48,
            'proximo_sequencial_remessa' => 6,
            'ativo' => true,
            'created_by' => $user->id,
        ]);
    }
}
