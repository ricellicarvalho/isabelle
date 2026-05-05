<?php

namespace App\Services\Boleto\Bancos;

use Eduardokum\LaravelBoleto\Boleto\Banco\Bradesco as LibBradesco;
use Eduardokum\LaravelBoleto\Util;

/**
 * Subclasse local do Bradesco do pacote eduardokum/laravel-boleto.
 *
 * Corrige dois problemas do pacote 0.1 (2016):
 *
 * 1. Fator de vencimento — incompatível com o Comunicado FEBRABAN 4015 para
 *    datas a partir de 22/02/2025 (fator > 9999). Aplica correção
 *    `((fator - 1000) % 9000) + 1000`.
 *
 * 2. DVs de agência e conta — `preProcessamento()` calcula os DVs via
 *    `Util::modulo11()`. Quando `agenciaDv` ou `contaDv` são informados
 *    explicitamente, este override os usa em vez do cálculo automático,
 *    permitindo configurar o valor real fornecido pelo banco.
 */
class Bradesco extends LibBradesco
{
    /** DV da agência configurado manualmente. null = calcula via modulo11. */
    public ?string $agenciaDv = null;

    /** DV da conta configurado manualmente. null = calcula via modulo11. */
    public ?string $contaDv = null;

    public function preProcessamento()
    {
        parent::preProcessamento();

        // Sobrescreve agenciaConta apenas quando ao menos um DV foi informado
        if ($this->agenciaDv !== null || $this->contaDv !== null) {
            $agDv = $this->agenciaDv ?? Util::modulo11($this->getAgencia());
            $ctDv = $this->contaDv  ?? Util::modulo11($this->getConta());
            $this->agenciaConta = sprintf('%s-%s %s-%s',
                $this->getAgencia(), $agDv,
                $this->getConta(),   $ctDv
            );
        }
    }

    protected function gerarCodigoBarras()
    {
        $fator = (int) Util::fatorVencimento($this->getDataVencimento());
        if ($fator > 9999) {
            $fator = (($fator - 1000) % 9000) + 1000;
        }
        $fatorStr = str_pad((string) $fator, 4, '0', STR_PAD_LEFT);

        $this->codigoBarras = $this->getBanco();
        $this->codigoBarras .= $this->numeroMoeda;
        $this->codigoBarras .= $fatorStr;
        $this->codigoBarras .= Util::numberFormatValue($this->getValor(), 10, 0);
        $this->codigoBarras .= Util::numberFormatGeral($this->getAgencia(), 4, 0);
        $this->codigoBarras .= Util::numberFormatGeral($this->getCarteira(), 2, 0);
        $this->codigoBarras .= $this->gerarNossoNumero();
        $this->codigoBarras .= Util::numberFormatGeral($this->getConta(), 7, 0);
        $this->codigoBarras .= '0';

        $r = Util::modulo11($this->codigoBarras, 9, 1);
        $dv = ($r == 0 || $r == 1 || $r == 10) ? 1 : (11 - $r);
        $this->codigoBarras = substr($this->codigoBarras, 0, 4) . $dv . substr($this->codigoBarras, 4);

        return $this->codigoBarras;
    }

    /**
     * Reimplementação do método privado homônimo do pacote — necessária por
     * estar marcado como `private` na classe pai.
     */
    private function gerarNossoNumero()
    {
        $nossoNumero = Util::numberFormatGeral($this->getNumero(), 11, 0);
        $dv = Util::modulo11($nossoNumero, 7, 0, 'P');
        $this->nossoNumero = $this->getCarteira() . '/' . $nossoNumero . '-' . $dv;

        return $nossoNumero;
    }
}
