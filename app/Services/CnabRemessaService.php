<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Models\BankRemessa;
use App\Services\Boleto\Cnab\BradescoRemessa as Bradesco;
use Eduardokum\LaravelBoleto\Cnab\Remessa\AbstractCnab;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Banco\Bb;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Banco\Caixa;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Banco\Itau;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Banco\Santander;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Detalhe;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CnabRemessaService
{
    /**
     * RN13 - Gera arquivo de remessa CNAB para os boletos informados.
     * RN14 - Boletos cancelados entram com instrução "Baixa de Título".
     * RN15 - Suporta layouts CNAB 240/400 conforme BankAccount.
     *
     * @param  Collection<int,BankBoleto>  $boletos
     */
    public static function generate(Collection $boletos): BankRemessa
    {
        $account = BankAccount::active();
        if (! $account) {
            throw new \RuntimeException('Nenhuma conta bancária ativa cadastrada.');
        }

        $elegiveis = $boletos->filter(fn (BankBoleto $b) => in_array($b->status, ['pendente', 'cancelado']))->values();

        if ($elegiveis->isEmpty()) {
            throw new \RuntimeException('Nenhum boleto elegível para remessa (apenas pendentes ou cancelados).');
        }

        return DB::transaction(function () use ($account, $elegiveis) {
            $sequencial = $account->reserveSequencialRemessa();

            $cnab = self::buildCnab($account, $sequencial);

            foreach ($elegiveis as $boleto) {
                self::addDetalhe($cnab, $boleto);
            }

            $conteudo = $cnab->gerar();

            $filename = sprintf(
                'remessa_%s_%s_%s.rem',
                $account->banco,
                str_pad((string) $sequencial, 6, '0', STR_PAD_LEFT),
                now()->format('YmdHis')
            );

            Storage::disk('local')->put('remessas/' . $filename, $conteudo);

            $remessa = BankRemessa::create([
                'sequencial_arquivo' => $sequencial,
                'data_geracao' => now(),
                'caminho_arquivo' => 'remessas/' . $filename,
                'quantidade_titulos' => $elegiveis->count(),
                'valor_total' => $elegiveis->sum('valor'),
                'layout' => 'cnab' . $account->layout_remessa,
                'status' => 'gerado',
                'created_by' => auth()->id() ?? $elegiveis->first()->created_by,
            ]);

            foreach ($elegiveis as $boleto) {
                $boleto->update([
                    'remessa_id' => $remessa->id,
                    'status' => $boleto->status === 'cancelado' ? 'cancelado' : 'emitido',
                ]);
            }

            return $remessa;
        });
    }

    protected static function buildCnab(BankAccount $account, int $sequencial): AbstractCnab
    {
        $cnab = match ($account->banco) {
            '001' => new Bb,
            '033' => new Santander,
            '104' => new Caixa,
            '237' => new Bradesco,
            '341' => new Itau,
            default => throw new \RuntimeException("Banco {$account->banco} não suportado."),
        };

        $cnab->idremessa = $sequencial;
        $cnab->carteira = $account->carteira;
        $cnab->agencia = $account->agencia;
        $cnab->conta = $account->conta;
        $cnab->cedenteNome = $account->cedente_nome;
        $cnab->cedenteCodigo = $account->convenio ?? $account->conta;

        // Bradesco usa contaRazao
        if (property_exists($cnab, 'contaRazao')) {
            $cnab->contaRazao = $account->convenio ?? '0';
        }

        // Workaround: o pacote v0.1 do Bradesco invoca `getAcencia()` (typo) em
        // addDetalhe(); o __call resolve buscando a propriedade `acencia`. Como
        // ela não existe, definimos dinamicamente para evitar a exceção.
        if ($cnab instanceof Bradesco) {
            $cnab->acencia = $account->agencia;
        }

        return $cnab;
    }

    protected static function addDetalhe(AbstractCnab $cnab, BankBoleto $boleto): void
    {
        $boleto->loadMissing(['receivable.client']);
        $client = $boleto->receivable?->client;

        $detalhe = new Detalhe;
        $detalhe->numero = (int) ltrim($boleto->nosso_numero, '0');
        $detalhe->numeroDocumento = $boleto->numero_documento;
        $detalhe->dataVencimento = $boleto->data_vencimento instanceof Carbon
            ? $boleto->data_vencimento
            : Carbon::parse($boleto->data_vencimento);
        $detalhe->dataDocumento = $boleto->created_at;
        $detalhe->valor = (float) $boleto->valor;
        $detalhe->especie = '01';
        $detalhe->aceite = 'N';

        // RN14 - Cancelados entram com instrução de baixa
        if ($boleto->status === 'cancelado') {
            $detalhe->ocorrencia = '02'; // PEDIDO_BAIXA
        } else {
            $detalhe->ocorrencia = '01'; // REMESSA
        }

        if ($client) {
            $detalhe->sacadoDocumento = preg_replace('/\D/', '', $client->cnpj_cpf ?? '');
            $detalhe->sacadoNome = $client->razao_social ?? '';
            $detalhe->sacadoEndereco = trim(($client->endereco ?? '') . ', ' . ($client->numero ?? ''));
            $detalhe->sacadoBairro = $client->bairro ?? '';
            $detalhe->sacadoCEP = preg_replace('/\D/', '', $client->cep ?? '00000000');
            $detalhe->sacadoCidade = $client->cidade ?? '';
            $detalhe->sacadoEstado = $client->uf ?? '';
        }

        $cnab->addDetalhe($detalhe);
    }
}
