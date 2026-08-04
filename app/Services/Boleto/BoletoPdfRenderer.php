<?php

namespace App\Services\Boleto;

use Eduardokum\LaravelBoleto\Boleto\Render\Pdf;

/**
 * Adapta o renderizador legado para devolver os bytes em vez de encerrar a requisição.
 */
class BoletoPdfRenderer extends Pdf
{
    private string $output = '';

    public function Output($name = '', $dest = 'I', $print = false): string
    {
        if ($print) {
            $this->IncludeJS("print('true');");
        }

        $this->output = (string) \fpdf\FPDF::Output($name, $dest);

        return $this->output;
    }

    public function bytes(): string
    {
        return $this->output;
    }
}
