<?php

namespace Database\Seeders;

use App\Models\NfseServiceCode;
use Illuminate\Database\Seeder;

class NfseServiceCodeSeeder extends Seeder
{
    /**
     * Códigos de serviço padrão mapeados por tipo de serviço do sistema.
     * Baseados na LC 116/2003 — ajustar conforme tabela municipal de Gurupi-TO.
     */
    public function run(): void
    {
        $adminId = \App\Models\User::where('is_admin', true)->value('id') ?? 1;

        $codigos = [
            [
                'tipo_servico'               => 'nr1',
                'descricao'                  => 'Consultoria Psicossocial — NR-1',
                'item_lista_servico'         => '17.01',
                'codigo_tributacao_municipio' => '17010',
                'codigo_cnae'               => '7020400',
                'aliquota'                  => 2.00,
            ],
            [
                'tipo_servico'               => 'consultoria',
                'descricao'                  => 'Consultoria em Saúde Ocupacional',
                'item_lista_servico'         => '17.01',
                'codigo_tributacao_municipio' => '17010',
                'codigo_cnae'               => '7020400',
                'aliquota'                  => 2.00,
            ],
            [
                'tipo_servico'               => 'treinamento',
                'descricao'                  => 'Treinamento e Capacitação Profissional',
                'item_lista_servico'         => '8.02',
                'codigo_tributacao_municipio' => '08020',
                'codigo_cnae'               => '8599604',
                'aliquota'                  => 2.00,
            ],
            [
                'tipo_servico'               => 'palestra',
                'descricao'                  => 'Palestra e Instrução — Saúde Mental',
                'item_lista_servico'         => '8.02',
                'codigo_tributacao_municipio' => '08020',
                'codigo_cnae'               => '8599604',
                'aliquota'                  => 2.00,
            ],
            [
                'tipo_servico'               => 'outro',
                'descricao'                  => 'Outros Serviços',
                'item_lista_servico'         => '17.01',
                'codigo_tributacao_municipio' => '17010',
                'codigo_cnae'               => null,
                'aliquota'                  => 2.00,
            ],
        ];

        foreach ($codigos as $dados) {
            NfseServiceCode::updateOrCreate(
                ['tipo_servico' => $dados['tipo_servico']],
                array_merge($dados, ['created_by' => $adminId, 'ativo' => true])
            );
        }
    }
}
