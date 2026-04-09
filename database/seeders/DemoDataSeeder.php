<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Receivable;
use App\Models\Supplier;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@isabelle.com.br')->first();

        if (! $admin) {
            $this->command?->warn('Admin user not found, skipping demo seed.');

            return;
        }

        $by = $admin->id;

        $categoriaReceita = Category::where('codigo', '1.1')->first()
            ?? Category::where('tipo', 'receita')->first();

        // Clientes (2)
        $cliente1 = Client::firstOrCreate(
            ['cnpj_cpf' => '11.222.333/0001-44'],
            [
                'tipo_pessoa' => 'pj',
                'razao_social' => 'Construtora Aurora LTDA',
                'nome_fantasia' => 'Aurora',
                'endereco' => 'Av. Paulista',
                'numero' => '1000',
                'bairro' => 'Bela Vista',
                'cidade' => 'São Paulo',
                'uf' => 'SP',
                'cep' => '01310-100',
                'telefone' => '(11) 3333-1000',
                'email' => 'contato@aurora.com.br',
                'contato_nome' => 'Maria Silva',
                'contato_telefone' => '(11) 99999-1000',
                'nr1_status' => 'em_andamento',
                'status' => 'ativo',
                'created_by' => $by,
            ]
        );

        $cliente2 = Client::firstOrCreate(
            ['cnpj_cpf' => '22.333.444/0001-55'],
            [
                'tipo_pessoa' => 'pj',
                'razao_social' => 'Indústria Bonança S/A',
                'nome_fantasia' => 'Bonança',
                'endereco' => 'Rua das Indústrias',
                'numero' => '500',
                'bairro' => 'Distrito Industrial',
                'cidade' => 'Campinas',
                'uf' => 'SP',
                'cep' => '13050-000',
                'telefone' => '(19) 3232-2000',
                'email' => 'contato@bonanca.com.br',
                'contato_nome' => 'João Pereira',
                'contato_telefone' => '(19) 98888-2000',
                'nr1_status' => 'pendente',
                'status' => 'ativo',
                'created_by' => $by,
            ]
        );

        // Fornecedores (2)
        Supplier::firstOrCreate(
            ['cnpj_cpf' => '33.444.555/0001-66'],
            [
                'nome' => 'Papelaria Central LTDA',
                'email' => 'vendas@papelariacentral.com.br',
                'telefone' => '(11) 3000-1010',
                'created_by' => $by,
            ]
        );

        Supplier::firstOrCreate(
            ['cnpj_cpf' => '44.555.666/0001-77'],
            [
                'nome' => 'Tech Solutions Informática ME',
                'email' => 'comercial@techsol.com.br',
                'telefone' => '(11) 3000-2020',
                'created_by' => $by,
            ]
        );

        // Contratos (2) — um já vencido (5 dias atrás)
        $contrato1 = Contract::firstOrCreate(
            ['numero' => 'CT-2026-0001'],
            [
                'client_id' => $cliente1->id,
                'category_id' => $categoriaReceita?->id,
                'tipo_servico' => 'nr1',
                'descricao' => 'Consultoria NR-1 com avaliação psicossocial completa.',
                'valor_total' => 12000.00,
                'forma_pagamento' => 'boleto',
                'quantidade_parcelas' => 3,
                'data_inicio' => Carbon::today()->subMonths(2),
                'data_fim' => Carbon::today()->subDays(5), // VENCIDO há 5 dias
                'status' => 'ativo',
                'created_by' => $by,
            ]
        );

        $contrato2 = Contract::firstOrCreate(
            ['numero' => 'CT-2026-0002'],
            [
                'client_id' => $cliente2->id,
                'category_id' => $categoriaReceita?->id,
                'tipo_servico' => 'treinamento',
                'descricao' => 'Treinamento de NR-1 para colaboradores.',
                'valor_total' => 6500.00,
                'forma_pagamento' => 'pix',
                'quantidade_parcelas' => 2,
                'data_inicio' => Carbon::today(),
                'data_fim' => Carbon::today()->addMonths(2),
                'status' => 'ativo',
                'created_by' => $by,
            ]
        );

        // Garante que existem receivables (caso o observer não tenha rodado por firstOrCreate)
        foreach ([$contrato1, $contrato2] as $contrato) {
            if ($contrato->receivables()->count() === 0) {
                app(\App\Observers\ContractObserver::class)->generateReceivables($contrato);
            }
        }

        // Marca uma parcela do contrato 1 como vencida para visualização
        Receivable::where('contract_id', $contrato1->id)
            ->orderBy('numero_parcela')
            ->first()
            ?->update(['status' => 'vencido']);

        // Notificação de exemplo no sino do Filament
        $admin->notify(new ContractExpiringNotification($contrato2, 30));
    }
}
