<?php

namespace App\Observers;

use App\Models\Nr1Cycle;
use Illuminate\Validation\ValidationException;

class Nr1CycleObserver
{
    public function saving(Nr1Cycle $cycle): void
    {
        $cycle->status = Nr1Cycle::statusFromChecklist($cycle->checklist ?? []);

        if ($cycle->contract_id === null) {
            return;
        }

        $contract = $cycle->contract()->first();

        if (! $contract || (int) $contract->client_id !== (int) $cycle->client_id) {
            throw ValidationException::withMessages([
                'contract_id' => 'O contrato selecionado não pertence ao cliente da NR-1.',
            ]);
        }

        if ($contract->tipo_servico !== 'nr1') {
            throw ValidationException::withMessages([
                'contract_id' => 'A NR-1 somente pode ser vinculada a um contrato do serviço NR-1.',
            ]);
        }

        if ($cycle->opened_by_contract_version_id !== null) {
            $versionBelongsToContract = $contract->versions()
                ->whereKey($cycle->opened_by_contract_version_id)
                ->whereIn('change_type', ['original', 'renewal'])
                ->exists();

            if (! $versionBelongsToContract) {
                throw ValidationException::withMessages([
                    'opened_by_contract_version_id' => 'A versão de abertura não pertence ao contrato NR-1 selecionado.',
                ]);
            }
        }
    }
}
