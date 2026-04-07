<?php

namespace App\Services\Boleto\Bancos;

use Eduardokum\LaravelBoleto\Boleto\Banco\Bradesco as LibBradesco;
use Eduardokum\LaravelBoleto\Util;

/**
 * Subclasse local do Bradesco do pacote eduardokum/laravel-boleto.
 *
 * O pacote 0.1 (2016) é incompatível com o Comunicado FEBRABAN 4015 — para
 * datas de vencimento a partir de 22/02/2025 o fator de vencimento ultrapassa
 * 9999 dias e o cálculo do pacote estoura o tamanho do código de barras (44 →
 * 45). Esta classe sobrescreve `gerarCodigoBarras` aplicando o fator corrigido
 * `((fator - 1000) % 9000) + 1000` quando necessário.
 */
class Bradesco extends LibBradesco
{
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
