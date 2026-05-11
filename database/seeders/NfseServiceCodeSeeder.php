<?php

namespace Database\Seeders;

use App\Models\NfseServiceCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NfseServiceCodeSeeder extends Seeder
{
    /**
     * Códigos de atividade autorizados pela Prefeitura de Gurupi-TO.
     *
     * tipo_servico     = código de 4 dígitos da prefeitura (ex: 0415)
     * item_lista_servico = código LC 116/2003 com ponto (ex: 4.15)
     * codigo_tributacao_municipio = cTribMun enviado à API (sem zero à esquerda para códigos 0xxx)
     */
    public function run(): void
    {
        DB::table('nfse_service_codes')->delete();

        $adminId = \App\Models\User::where('is_admin', true)->value('id') ?? 1;

        $codigos = [
            // ── Tecnologia da Informação (LC 116 item 1.xx) ───────────────────
            [
                'tipo_servico'                => '0101',
                'descricao'                   => 'Análise e desenvolvimento de sistemas.',
                'item_lista_servico'          => '1.01',
                'codigo_tributacao_municipio' => '101',
            ],
            [
                'tipo_servico'                => '0102',
                'descricao'                   => 'Programação.',
                'item_lista_servico'          => '1.02',
                'codigo_tributacao_municipio' => '102',
            ],
            [
                'tipo_servico'                => '0103',
                'descricao'                   => 'Processamento, armazenamento ou hospedagem de dados, textos, imagens, vídeos, páginas eletrônica de dados, aplicativos e sistemas de informação, entre outros formatos, e congêneres.',
                'item_lista_servico'          => '1.03',
                'codigo_tributacao_municipio' => '103',
            ],
            [
                'tipo_servico'                => '0104',
                'descricao'                   => 'Elaboração de programas de computadores, inclusive de jogos eletrônicos, independentemente da arquitetura construtiva da máquina em que o programa será executado, incluindo tabletes, smartphones e congêneres.',
                'item_lista_servico'          => '1.04',
                'codigo_tributacao_municipio' => '104',
            ],
            [
                'tipo_servico'                => '0105',
                'descricao'                   => 'Licenciamento ou cessão de direito de uso de programas de computação.',
                'item_lista_servico'          => '1.05',
                'codigo_tributacao_municipio' => '105',
            ],
            [
                'tipo_servico'                => '0106',
                'descricao'                   => 'Assessoria e consultoria em informática.',
                'item_lista_servico'          => '1.06',
                'codigo_tributacao_municipio' => '106',
            ],
            [
                'tipo_servico'                => '0107',
                'descricao'                   => 'Suporte técnico em informática, inclusive instalação, configuração e manutenção de programas de computação e bancos de dados.',
                'item_lista_servico'          => '1.07',
                'codigo_tributacao_municipio' => '107',
            ],
            [
                'tipo_servico'                => '0108',
                'descricao'                   => 'Planejamento, confecção, manutenção e atualização de páginas eletrônicas.',
                'item_lista_servico'          => '1.08',
                'codigo_tributacao_municipio' => '108',
            ],

            // ── Saúde (LC 116 item 4.xx) ──────────────────────────────────────
            [
                'tipo_servico'                => '0401',
                'descricao'                   => 'Medicina e biomedicina.',
                'item_lista_servico'          => '4.01',
                'codigo_tributacao_municipio' => '401',
            ],
            [
                'tipo_servico'                => '0408',
                'descricao'                   => 'Terapia ocupacional, fisioterapia e fonoaudiologia.',
                'item_lista_servico'          => '4.08',
                'codigo_tributacao_municipio' => '408',
            ],
            [
                'tipo_servico'                => '0409',
                'descricao'                   => 'Terapias de qualquer espécie destinadas ao tratamento físico, orgânico e mental.',
                'item_lista_servico'          => '4.09',
                'codigo_tributacao_municipio' => '409',
            ],
            [
                'tipo_servico'                => '0411',
                'descricao'                   => 'Obstetrícia.',
                'item_lista_servico'          => '4.11',
                'codigo_tributacao_municipio' => '411',
            ],
            [
                'tipo_servico'                => '0415',
                'descricao'                   => 'Psicanálise.',
                'item_lista_servico'          => '4.15',
                'codigo_tributacao_municipio' => '415',
            ],
            [
                'tipo_servico'                => '0416',
                'descricao'                   => 'Psicologia.',
                'item_lista_servico'          => '4.16',
                'codigo_tributacao_municipio' => '416',
            ],

            // ── Educação / Treinamento (LC 116 item 8.xx) ────────────────────
            [
                'tipo_servico'                => '0802',
                'descricao'                   => 'Instrução, treinamento, orientação pedagógica e educacional, avaliação de conhecimentos de qualquer natureza.',
                'item_lista_servico'          => '8.02',
                'codigo_tributacao_municipio' => '802',
            ],

            // ── Consultoria / Assessoria (LC 116 item 17.xx) ─────────────────
            [
                'tipo_servico'                => '1701',
                'descricao'                   => 'Assessoria ou consultoria de qualquer natureza, não contida em outros itens desta lista; análise, exame, pesquisa, coleta, compilação e fornecimento de dados e informações de qualquer natureza, inclusive cadastro e similares.',
                'item_lista_servico'          => '17.01',
                'codigo_tributacao_municipio' => '701',
            ],
            [
                'tipo_servico'                => '1706',
                'descricao'                   => 'Propaganda e publicidade, inclusive promoção de vendas, planejamento de campanhas ou sistemas de publicidade, elaboração de desenhos, textos e demais materiais publicitários.',
                'item_lista_servico'          => '17.06',
                'codigo_tributacao_municipio' => '706',
            ],
            [
                'tipo_servico'                => '1720',
                'descricao'                   => 'Consultoria e assessoria econômica ou financeira.',
                'item_lista_servico'          => '17.20',
                'codigo_tributacao_municipio' => '720',
            ],
            [
                'tipo_servico'                => '1722',
                'descricao'                   => 'Cobrança em geral.',
                'item_lista_servico'          => '17.22',
                'codigo_tributacao_municipio' => '722',
            ],
            [
                'tipo_servico'                => '1724',
                'descricao'                   => 'Apresentação de palestras, conferências, seminários e congêneres.',
                'item_lista_servico'          => '17.24',
                'codigo_tributacao_municipio' => '724',
            ],

            // ── Outros (LC 116 item 36.xx) ────────────────────────────────────
            [
                'tipo_servico'                => '3601',
                'descricao'                   => 'Serviços de meteorologia.',
                'item_lista_servico'          => '36.01',
                'codigo_tributacao_municipio' => '601',
            ],
        ];

        foreach ($codigos as $dados) {
            NfseServiceCode::updateOrCreate(
                ['tipo_servico' => $dados['tipo_servico']],
                array_merge($dados, [
                    'aliquota'   => 2.01,
                    'codigo_cnae' => null,
                    'ativo'      => true,
                    'created_by' => $adminId,
                ])
            );
        }
    }
}
