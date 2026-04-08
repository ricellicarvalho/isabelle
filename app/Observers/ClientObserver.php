<?php

namespace App\Observers;

use App\Models\Client;

class ClientObserver
{
    /**
     * RN07 — Ao salvar um Client, sincroniza o nr1_status com o checklist:
     * - 5/5 itens → regularizada
     * - qualquer item marcado → em_andamento
     * - nenhum → pendente
     */
    public function saving(Client $client): void
    {
        $checklist = $client->nr1_checklist ?? [];
        $itens = ['avaliacao', 'devolutiva', 'plano', 'treinamento', 'relatorio'];
        $done = count(array_filter($itens, fn ($i) => ! empty($checklist[$i])));

        $client->nr1_status = match (true) {
            $done === count($itens) => 'regularizada',
            $done > 0              => 'em_andamento',
            default                => 'pendente',
        };
    }
}
