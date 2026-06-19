<?php

namespace App\Services\Boleto\Cnab;

use Eduardokum\LaravelBoleto\Cnab\Remessa\Banco\Bradesco as LibBradescoCnab;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Detalhe;
use Eduardokum\LaravelBoleto\Util;

/**
 * Subclasse local do CNAB Bradesco do pacote eduardokum/laravel-boleto.
 *
 * O método `addDetalhe` da versão 0.1 invoca `getAcencia()` (typo) que cai no
 * `__call` do AbstractCnab procurando a propriedade `acencia`. Declaramos a
 * propriedade aqui para evitar a `Exception("Método acencia não existe")` e a
 * deprecation de criação de propriedade dinâmica em PHP 8.2+.
 */
class BradescoRemessa extends LibBradescoCnab
{
    public $acencia;

    /**
     * Sobrescreve addDetalhe() do vendor para corrigir divergências apontadas
     * pelo Bradesco na homologação do CNAB 400 (conforme Manual 4008.524.0121):
     * - 063-065: só deve ser "237" para débito automático em conta; o sistema
     *   não usa débito automático, então deve ser zeros.
     * - 066: indicativo de multa só aceita "0" (sem multa) ou "2" (com multa)
     *   no manual; o vendor usava espaço em branco quando não há multa.
     * - 082: o vendor chama Util::modulo11() com os parâmetros trocados
     *   (fator=7, base=0, resto='P'), divergindo do algoritmo oficial do
     *   manual (fator=2, base=7, resto=0, dígito "P" quando o resto for 1).
     * - 105: só deve ser "R" quando o registro tipo 3 (rateio) é enviado; o
     *   sistema não gera rateio, então deve ficar em branco.
     * - 140-142 / 143-147: são campos numéricos (banco encarregado da
     *   cobrança / agência depositária) que devem ser zeros, não brancos.
     * - 174-179 / 180-192: o sistema não suporta desconto; zeramos os dois
     *   campos em vez de herdar a data de vencimento como "data limite de
     *   desconto" com valor de desconto zerado (inconsistência apontada).
     */
    public function addDetalhe(Detalhe $detalhe)
    {
        $this->iniciaDetalhe();

        $idempresa = '0';
        $idempresa .= Util::formatCnab('N', $this->getCarteira('21'), 3);
        $idempresa .= Util::formatCnab('N', $this->getAcencia(), 5);
        $idempresa .= Util::formatCnab('N', $this->getConta(), 7);
        $idempresa .= Util::modulo11($this->getConta());

        $dvNossoNumero = Util::modulo11(
            Util::formatCnab('N', $this->getCarteira('21'), 2) . Util::formatCnab('N', $detalhe->getNumero(), 11),
            2,
            7,
            0,
            'P'
        );
        $dvNossoNumero = $detalhe->getNumero() > 0 ? $dvNossoNumero : 0;

        $this->add(1, 1, '1');

        $this->add(2, 6, Util::formatCnab('A', '', 5));
        $this->add(7, 7, '');
        $this->add(8, 12, Util::formatCnab('A', '', 5));
        $this->add(13, 19, Util::formatCnab('A', '', 7));
        $this->add(20, 20, '');

        if ($detalhe->getTaxaMulta()) {
            $tipoMulta = 2;
            $multa = $detalhe->getTaxaMulta();
        } elseif ($detalhe->getValorMulta()) {
            $tipoMulta = 0;
            $multa = $detalhe->getValorMulta();
        } else {
            $tipoMulta = 0;
            $multa = 0;
        }

        $this->add(21, 37, Util::formatCnab('A', $idempresa, 17));
        $this->add(38, 62, Util::formatCnab('A', $detalhe->getNumeroControleString(), 25));
        $this->add(63, 65, Util::formatCnab('N', 0, 3));
        $this->add(66, 66, $tipoMulta);
        $this->add(67, 70, Util::formatCnab('N', $multa, 4, 2));
        $this->add(71, 81, Util::formatCnab('N', $detalhe->getNumero(), 11));
        $this->add(82, 82, $dvNossoNumero);
        $this->add(83, 92, Util::formatCnab('N', $detalhe->getValorDesconto(), 10, 2));
        $this->add(93, 93, ($detalhe->getNumero() > 0 ? '2' : '1'));
        $this->add(94, 94, ($detalhe->getNumero() > 0 ? 'N' : ' '));
        $this->add(95, 104, '');
        $this->add(105, 105, ' ');
        $this->add(106, 106, '2');
        $this->add(107, 108, '');
        $this->add(109, 110, Util::formatCnab('N', $detalhe->getOcorrencia(), 2));
        $this->add(111, 120, Util::formatCnab('A', $detalhe->getNumeroDocumento(), 10));
        $this->add(121, 126, Util::formatCnab('D', $detalhe->getDataVencimento(), 6));
        $this->add(127, 139, Util::formatCnab('N', $detalhe->getValor(), 13, 2));
        $this->add(140, 142, Util::formatCnab('N', 0, 3));
        $this->add(143, 147, Util::formatCnab('N', 0, 5));
        $this->add(148, 149, $detalhe->getEspecie('01'));
        $this->add(150, 150, $detalhe->getAceite('N'));
        $this->add(151, 156, Util::formatCnab('D', $detalhe->getDataDocumento(), 6));
        $this->add(157, 158, $detalhe->getInstrucao1('00'));
        $this->add(159, 160, $detalhe->getInstrucao2('00'));
        $this->add(161, 173, Util::formatCnab('N', $detalhe->getValorMora(), 13, 2));
        $this->add(174, 179, '000000');
        $this->add(180, 192, Util::formatCnab('N', 0, 13, 2));
        $this->add(193, 205, Util::formatCnab('N', $detalhe->getvalorIOF(), 13, 2));
        $this->add(206, 218, Util::formatCnab('N', $detalhe->getValorAbatimento(), 13, 2));
        $this->add(219, 220, Util::formatCnab('NL', $detalhe->getSacadoTipoDocumento(), 2));
        $this->add(221, 234, Util::formatCnab('L', $detalhe->getSacadoDocumento(), 14));
        $this->add(235, 274, Util::formatCnab('A', $detalhe->getSacadoNome(), 40));
        $this->add(275, 314, Util::formatCnab('A', $detalhe->getSacadoEndereco(), 40));
        $this->add(315, 326, '');
        $this->add(327, 331, substr($detalhe->getSacadoCEP(), 0, 5));
        $this->add(332, 334, substr($detalhe->getSacadoCEP(), -3));
        $this->add(335, 394, Util::formatCnab('A', $detalhe->getSacadorAvalista(), 60));
        $this->add(395, 400, Util::formatCnab('N', $this->iRegistros + 1, 6));

        return $this;
    }
}
