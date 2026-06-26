<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Models\BankRetorno;
use App\Models\Receivable;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Detalhe;
use Eduardokum\LaravelBoleto\Cnab\Retorno\Factory as RetornoFactory;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * M10 — Parser de Retorno CNAB.
 *
 * Responsável por:
 *  - Ler arquivos .ret enviados pelo banco (via eduardokum/laravel-boleto)
 *  - Localizar BankBoletos pelo nosso_numero
 *  - Atualizar status (pago/cancelado/emitido) e datas/valores
 *  - Quitar Receivable correspondente em caso de liquidação
 *  - Persistir um BankRetorno com totais e log da importação
 */
class CnabRetornoService
{
    /**
     * Processa um arquivo de retorno e retorna o registro BankRetorno criado.
     *
     * @param string $absolutePath Caminho absoluto do arquivo .ret
     * @param string $originalName Nome original do arquivo (para exibição)
     * @param BankAccount|null $bankAccount Conta bancária associada (opcional)
     */
    public function processar(string $absolutePath, string $originalName, ?BankAccount $bankAccount = null): BankRetorno
    {
        $cnab = RetornoFactory::make($absolutePath);
        $cnab->processar();

        $header = $cnab->getHeader();
        $detalhes = $cnab->getDetalhes();

        $totais = [
            'liquidados' => 0,
            'baixados' => 0,
            'entradas' => 0,
            'alterados' => 0,
            'erros' => 0,
            'nao_encontrados' => 0,
            'valor_total' => 0.0,
        ];

        $log = [];

        return DB::transaction(function () use (
            $cnab,
            $header,
            $detalhes,
            &$totais,
            &$log,
            $absolutePath,
            $originalName,
            $bankAccount
        ) {
            // Salva uma cópia do arquivo dentro de storage/app/retornos/
            $stored = $this->storeFile($absolutePath, $originalName);

            $retorno = BankRetorno::create([
                'bank_account_id' => $bankAccount?->id,
                'nome_arquivo' => $originalName,
                'caminho_arquivo' => $stored,
                'banco' => method_exists($cnab, 'getBanco') ? $cnab->getBanco() : null,
                'layout' => '400',
                'data_arquivo' => $this->extractDate($header),
                'data_processamento' => now(),
                'quantidade_titulos' => count($detalhes),
                'valor_total' => 0,
                'created_by' => auth()->id() ?? 1,
            ]);

            foreach ($detalhes as $detalhe) {
                $entry = $this->aplicarDetalhe($detalhe, $retorno);
                $log[] = $entry;

                match (true) {
                    $entry['status'] === 'liquidado' => $totais['liquidados']++,
                    $entry['status'] === 'baixado' => $totais['baixados']++,
                    $entry['status'] === 'entrada' => $totais['entradas']++,
                    $entry['status'] === 'alterado' => $totais['alterados']++,
                    $entry['status'] === 'erro' => $totais['erros']++,
                    $entry['status'] === 'nao_encontrado' => $totais['nao_encontrados']++,
                    default => null,
                };

                if ($entry['status'] === 'liquidado') {
                    $totais['valor_total'] += (float) ($entry['valor'] ?? 0);
                }
            }

            $retorno->update([
                'quantidade_liquidados' => $totais['liquidados'],
                'quantidade_baixados' => $totais['baixados'],
                'quantidade_entradas' => $totais['entradas'],
                'quantidade_alterados' => $totais['alterados'],
                'quantidade_erros' => $totais['erros'],
                'quantidade_nao_encontrados' => $totais['nao_encontrados'],
                'valor_total' => $totais['valor_total'],
                'log' => $log,
            ]);

            return $retorno->fresh();
        });
    }

    /**
     * @return array{nosso_numero:string,status:string,valor?:float,mensagem?:string}
     */
    protected function aplicarDetalhe($detalhe, BankRetorno $retorno): array
    {
        $nossoNumeroRaw = method_exists($detalhe, 'get')
            ? (string) $detalhe->get('nossoNumero')
            : (string) ($detalhe->nossoNumero ?? '');

        // O retorno do banco normalmente traz o nosso número COM o dígito
        // verificador no final (ex.: "000000000054" = nosso "00000000005" + DV "4"),
        // enquanto o BankBoleto é armazenado SEM o DV. Geramos candidatos com e
        // sem o último dígito para casar nas duas situações.
        $candidatos = [];
        $semZeros = ltrim($nossoNumeroRaw, '0');
        if ($semZeros !== '') {
            $candidatos[] = $semZeros;
        }
        if (strlen($nossoNumeroRaw) > 1) {
            $semDv = ltrim(substr($nossoNumeroRaw, 0, -1), '0');
            if ($semDv !== '') {
                $candidatos[] = $semDv;
            }
        }
        $candidatos = array_values(array_unique($candidatos));

        $tipo = $detalhe->getTipoOcorrencia();

        $boleto = BankBoleto::query()
            ->where(function ($query) use ($candidatos, $nossoNumeroRaw) {
                foreach ($candidatos as $candidato) {
                    $query->orWhereRaw('TRIM(LEADING "0" FROM nosso_numero) = ?', [$candidato]);
                }
                $query->orWhere('nosso_numero', $nossoNumeroRaw);
            })
            ->first();

        // Para o log usamos o nosso número efetivamente armazenado quando casamos,
        // caso contrário o valor limpo lido do arquivo.
        $nossoNumero = $boleto?->nosso_numero ?? ($semZeros !== '' ? $semZeros : $nossoNumeroRaw);

        if (! $boleto) {
            return [
                'nosso_numero' => $nossoNumero,
                'status' => 'nao_encontrado',
                'mensagem' => 'Boleto não localizado pelo nosso_número',
            ];
        }

        $valor = (float) ($detalhe->get('valorRecebido') ?: $detalhe->get('valor') ?: 0);

        // A biblioteca eduardokum/laravel-boleto devolve objetos \Carbon\Carbon
        // (classe base), por isso checamos via CarbonInterface — um teste contra
        // Illuminate\Support\Carbon falharia e cairia indevidamente no now().
        //
        // Para "data de pagamento" usamos sempre a DATA DE CRÉDITO (dataCredito),
        // que é a data em que o valor é efetivamente creditado/conciliado em conta.
        // Só caímos para a data da ocorrência (e, em último caso, now()) quando o
        // arquivo não traz a data de crédito.
        $dataOcorrencia = $detalhe->get('dataOcorrencia');
        $dataCredito = $detalhe->get('dataCredito');
        $dataPagamento = $dataCredito instanceof CarbonInterface
            ? $dataCredito
            : ($dataOcorrencia instanceof CarbonInterface ? $dataOcorrencia : now());

        switch ($tipo) {
            case Detalhe::OCORRENCIA_LIQUIDADA:
                $boleto->update([
                    'status' => 'pago',
                    'valor_pago' => $valor ?: $boleto->valor,
                    'data_pagamento' => $dataPagamento->toDateString(),
                    'bank_retorno_id' => $retorno->id,
                ]);

                // Quita a parcela correspondente em Receivables
                if ($boleto->receivable_id && ($receivable = Receivable::find($boleto->receivable_id))) {
                    $receivable->update([
                        'status' => 'pago',
                        'valor_pago' => $valor ?: $receivable->valor,
                        'data_pagamento' => $dataPagamento->toDateString(),
                        'forma_pagamento' => 'boleto',
                    ]);
                }

                return [
                    'nosso_numero' => $nossoNumero,
                    'status' => 'liquidado',
                    'valor' => $valor,
                    'mensagem' => 'Boleto liquidado',
                ];

            case Detalhe::OCORRENCIA_BAIXADA:
                $boleto->update([
                    'status' => 'cancelado',
                    'bank_retorno_id' => $retorno->id,
                ]);

                return [
                    'nosso_numero' => $nossoNumero,
                    'status' => 'baixado',
                    'mensagem' => 'Boleto baixado pelo banco',
                ];

            case Detalhe::OCORRENCIA_ENTRADA:
                $boleto->update([
                    'status' => 'emitido',
                    'bank_retorno_id' => $retorno->id,
                ]);

                return [
                    'nosso_numero' => $nossoNumero,
                    'status' => 'entrada',
                    'mensagem' => 'Confirmação de entrada/registro',
                ];

            case Detalhe::OCORRENCIA_ERRO:
                return [
                    'nosso_numero' => $nossoNumero,
                    'status' => 'erro',
                    'mensagem' => method_exists($detalhe, 'getErro')
                        ? (string) $detalhe->getErro()
                        : 'Erro reportado pelo banco',
                ];

            case Detalhe::OCORRENCIA_ALTERACAO:
            default:
                return [
                    'nosso_numero' => $nossoNumero,
                    'status' => 'alterado',
                    'mensagem' => 'Ocorrência informativa/alteração',
                ];
        }
    }

    protected function storeFile(string $absolutePath, string $originalName): string
    {
        $contents = @file_get_contents($absolutePath);
        if ($contents === false) {
            return '';
        }

        $filename = 'retornos/' . date('Ymd_His') . '_' . $originalName;
        Storage::disk('local')->put($filename, $contents);

        return $filename;
    }

    protected function extractDate($header): ?CarbonInterface
    {
        $data = method_exists($header, 'get') ? $header->get('data') : ($header->data ?? null);

        return $data instanceof CarbonInterface ? $data : null;
    }
}
