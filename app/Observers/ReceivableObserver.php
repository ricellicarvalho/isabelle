<?php

namespace App\Observers;

use App\Models\Receivable;

class ReceivableObserver
{
    /**
     * RN14 - Quando uma parcela é cancelada, marcar boletos vinculados como
     * cancelados com instrução de baixa, para entrarem na próxima remessa.
     */
    public function updated(Receivable $receivable): void
    {
        if (! $receivable->wasChanged('status')) {
            return;
        }

        if ($receivable->status !== 'cancelado') {
            return;
        }

        $receivable->bankBoletos()
            ->whereIn('status', ['pendente', 'emitido'])
            ->update([
                'status' => 'cancelado',
                'instrucao_remessa' => 'BAIXA',
                // Limpa remessa_id para o boleto poder entrar em uma nova remessa de baixa
                'remessa_id' => null,
            ]);
    }
}
