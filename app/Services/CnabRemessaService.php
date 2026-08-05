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
use RuntimeException;

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
            throw new RuntimeException('Nenhuma conta bancária ativa cadastrada.');
        }

        $elegiveis = $boletos->filter(fn (BankBoleto $b) => in_array($b->status, ['pendente', 'cancelado']))->values();

        if ($elegiveis->isEmpty()) {
            throw new RuntimeException('Nenhum boleto elegível para remessa (apenas pendentes ou cancelados).');
        }

        return DB::transaction(function () use ($account, $elegiveis) {
            $sequencial = $account->reserveSequencialRemessa();

            return self::createRemessa($account, $elegiveis, $sequencial);
        });
    }

    /**
     * Gera uma remessa corretiva com sequencial definido para boletos que ja
     * foram incluidos em remessas anteriores rejeitadas pelo banco.
     *
     * @param  Collection<int,BankBoleto>  $boletos
     */
    public static function generateCorrective(Collection $boletos, int $sequencial): BankRemessa
    {
        $account = BankAccount::active();
        if (! $account) {
            throw new RuntimeException('Nenhuma conta bancária ativa cadastrada.');
        }

        if ($boletos->isEmpty()) {
            throw new RuntimeException('Nenhum boleto informado para remessa corretiva.');
        }

        if (BankRemessa::query()->where('sequencial_arquivo', $sequencial)->exists()) {
            throw new RuntimeException("A remessa sequencial {$sequencial} já existe.");
        }

        return DB::transaction(function () use ($account, $boletos, $sequencial) {
            $remessa = self::createRemessa($account, $boletos->values(), $sequencial);

            $account->refresh();
            if ($account->proximo_sequencial_remessa <= $sequencial) {
                $account->update(['proximo_sequencial_remessa' => $sequencial + 1]);
            }

            return $remessa;
        });
    }

    public static function filename(int $sequencial): string
    {
        return sprintf('REMESSA%06d.REM', $sequencial);
    }

    protected static function buildCnab(BankAccount $account, int $sequencial): AbstractCnab
    {
        $cnab = match ($account->banco) {
            '001' => new Bb,
            '033' => new Santander,
            '104' => new Caixa,
            '237' => new Bradesco,
            '341' => new Itau,
            default => throw new RuntimeException("Banco {$account->banco} não suportado."),
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

    /**
     * @param  Collection<int,BankBoleto>  $boletos
     */
    protected static function createRemessa(BankAccount $account, Collection $boletos, int $sequencial): BankRemessa
    {
        $cnab = self::buildCnab($account, $sequencial);

        foreach ($boletos as $boleto) {
            self::addDetalhe($cnab, $boleto);
        }

        $conteudo = $cnab->gerar();
        $path = 'remessas/' . self::filename($sequencial);

        Storage::disk('local')->put($path, $conteudo);

        $remessa = BankRemessa::create([
            'sequencial_arquivo' => $sequencial,
            'data_geracao' => now(),
            'caminho_arquivo' => $path,
            'quantidade_titulos' => $boletos->count(),
            'valor_total' => $boletos->sum('valor'),
            'layout' => 'cnab' . $account->layout_remessa,
            'status' => 'gerado',
            'created_by' => auth()->id() ?? $boletos->first()->created_by,
        ]);

        foreach ($boletos as $boleto) {
            $boleto->update([
                'remessa_id' => $remessa->id,
                'status' => $boleto->status === 'cancelado' ? 'cancelado' : 'emitido',
            ]);
        }

        return $remessa;
    }
}
