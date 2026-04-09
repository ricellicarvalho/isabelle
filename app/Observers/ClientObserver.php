<?php

namespace App\Observers;

use App\Models\Client;

class ClientObserver
{
    /**
     * RN07 — Ao salvar um Client, sincroniza o nr1_status com o checklist:
     * - Etapas 1-5 concluídas → finalizada
     * - Etapas 1-4 concluídas → regularizada
     * - Etapas 1-3 concluídas → em_andamento
     * - Caso contrário → pendente
     */
    public function saving(Client $client): void
    {
        $checklist = $client->nr1_checklist ?? [];

        $etapa1 = ! empty($checklist['etapa1']);
        $etapa2 = ! empty($checklist['etapa2']);
        $etapa3 = ! empty($checklist['etapa3']);
        $etapa4 = ! empty($checklist['etapa4']);
        $etapa5 = ! empty($checklist['etapa5']);

        $client->nr1_status = match (true) {
            $etapa1 && $etapa2 && $etapa3 && $etapa4 && $etapa5 => 'finalizada',
            $etapa1 && $etapa2 && $etapa3 && $etapa4            => 'regularizada',
            $etapa1 && $etapa2 && $etapa3                       => 'em_andamento',
            default                                              => 'pendente',
        };
    }
}
