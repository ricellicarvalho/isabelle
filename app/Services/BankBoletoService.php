<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Models\Receivable;
use App\Services\Boleto\Bancos\Bradesco;
use Eduardokum\LaravelBoleto\Boleto\AbstractBoleto;
use Eduardokum\LaravelBoleto\Boleto\Banco\Bb;
use Eduardokum\LaravelBoleto\Boleto\Banco\Caixa;
use Eduardokum\LaravelBoleto\Boleto\Banco\Itau;
use Eduardokum\LaravelBoleto\Boleto\Banco\Santander;
use Illuminate\Support\Facades\Storage;

class BankBoletoService
{
    /**
     * RN12 - Geração Única de Nosso Número via lock pessimista na BankAccount.
     */
    public static function generateNossoNumero(?BankAccount $account = null): string
    {
        $account ??= BankAccount::active();
        if (! $account) {
            // Fallback incremental simples — usado apenas se nenhuma BankAccount estiver cadastrada
            $last = (int) BankBoleto::withTrashed()->max('id');

            return str_pad((string) ($last + 1), 11, '0', STR_PAD_LEFT);
        }

        $next = $account->reserveNossoNumero();

        return str_pad((string) $next, 11, '0', STR_PAD_LEFT);
    }

    /**
     * Cria um BankBoleto a partir de um Receivable, herdando valor e
     * data_vencimento (RN16). Calcula código de barras e linha digitável
     * imediatamente para que fiquem disponíveis sem precisar gerar o PDF.
     */
    public static function createFromReceivable(Receivable $receivable, array $overrides = []): BankBoleto
    {
        $account = BankAccount::active();
        $carteira = $account?->carteira;

        $boleto = BankBoleto::create(array_merge([
            'receivable_id'    => $receivable->id,
            'nosso_numero'     => self::generateNossoNumero($account),
            'numero_documento' => (string) $receivable->id,
            'carteira'         => $carteira,
            'valor'            => $receivable->valor,
            'data_vencimento'  => $receivable->data_vencimento,
            'status'           => 'pendente',
            'created_by'       => auth()->id() ?? $receivable->created_by,
        ], $overrides));

        // Computa código de barras e linha digitável na criação (silencia falha
        // para não bloquear o cadastro caso a conta bancária esteja incompleta).
        if ($account) {
            try {
                self::buildLibBoleto($boleto);
                $boleto->refresh();
            } catch (\Throwable) {
                // Boleto criado sem barcode — será calculado ao gerar o PDF
            }
        }

        return $boleto;
    }

    /**
     * Renderiza o PDF do boleto via eduardokum/laravel-boleto.
     */
    public static function renderPdf(BankBoleto $boleto): string
    {
        $boletoLib = self::buildLibBoleto($boleto);

        // O pacote v0.1 usa utf8_decode() (deprecated em PHP 8.2). Silencia
        // apenas a renderização para não poluir os logs.
        $previous = error_reporting();
        error_reporting($previous & ~E_DEPRECATED & ~E_USER_DEPRECATED);
        try {
            return $boletoLib->render();
        } finally {
            error_reporting($previous);
        }
    }

    /**
     * Constrói a instância do pacote Eduardokum/laravel-boleto a partir do
     * BankBoleto + BankAccount + Receivable + Cliente.
     */
    public static function buildLibBoleto(BankBoleto $boleto): AbstractBoleto
    {
        $account = BankAccount::active();
        if (! $account) {
            throw new \RuntimeException('Nenhuma conta bancária cadastrada. Cadastre em Configurações → Contas Bancárias.');
        }

        $boleto->loadMissing(['receivable.client']);
        $receivable = $boleto->receivable;
        $client     = $receivable?->client;

        $libBoleto = match ($account->banco) {
            '001' => new Bb,
            '033' => new Santander,
            '104' => new Caixa,
            '237' => new Bradesco,
            '341' => new Itau,
            default => throw new \RuntimeException("Banco {$account->banco} não suportado pelo pacote."),
        };

        $libBoleto->agencia  = $account->agencia;
        $libBoleto->conta    = $account->conta;
        $libBoleto->carteira = $boleto->carteira ?? $account->carteira;
        $libBoleto->numero   = (int) ltrim($boleto->nosso_numero, '0');

        // DVs opcionais — quando informados sobrescrevem o cálculo automático via modulo11
        if ($libBoleto instanceof Bradesco) {
            if (filled($account->agencia_dv)) {
                $libBoleto->agenciaDv = $account->agencia_dv;
            }
            if (filled($account->conta_dv)) {
                $libBoleto->contaDv = $account->conta_dv;
            }
        }

        $libBoleto->dataVencimento = $boleto->data_vencimento;
        $libBoleto->valor          = (float) $boleto->valor;

        // CNPJ sempre como dígitos puros para o maskString da biblioteca não duplicar separadores
        $libBoleto->cedenteDocumento = preg_replace('/\D/', '', $account->cedente_documento ?? '');
        $libBoleto->cedenteNome      = $account->cedente_nome;
        $libBoleto->cedenteEndereco  = $account->cedente_endereco ?? '';
        $libBoleto->cedenteCidadeUF  = $account->cedente_cidade_uf ?? '';

        // identificacao = nome do cedente (aparece como 1ª linha do bloco ao lado do logo no PDF)
        $libBoleto->identificacao = $account->cedente_nome;

        $libBoleto->especieDocumento = 'DM';
        $libBoleto->dataDocumento    = $boleto->created_at;

        // Logo da empresa: caminho absoluto para arquivo armazenado em storage/app/public/logos/
        if (filled($account->logo_path) && Storage::disk('public')->exists($account->logo_path)) {
            $libBoleto->logo = Storage::disk('public')->path($account->logo_path);
        }

        if ($client) {
            $libBoleto->sacadoDocumento = preg_replace('/\D/', '', $client->cnpj_cpf ?? '');
            $libBoleto->sacadoNome      = $client->razao_social ?? '';
            $libBoleto->sacadoEndereco  = trim(($client->endereco ?? '') . ', ' . ($client->numero ?? ''));
            $libBoleto->sacadoCidadeUF  = trim(($client->cidade ?? '') . '/' . ($client->uf ?? ''));
        }

        $libBoleto->processar();

        // Atualiza os campos calculados no model
        $boleto->update([
            'codigo_barras'   => $libBoleto->getCodigoBarras(),
            'linha_digitavel' => $libBoleto->getLinha(),
        ]);

        return $libBoleto;
    }
}
