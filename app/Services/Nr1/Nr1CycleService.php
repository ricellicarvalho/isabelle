<?php

namespace App\Services\Nr1;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Nr1Cycle;
use Illuminate\Validation\ValidationException;

class Nr1CycleService
{
    public function openForVersion(Contract $contract, ContractVersion $version, ?int $userId = null): ?Nr1Cycle
    {
        if ($version->tipo_servico !== 'nr1') {
            return null;
        }

        if (! in_array($version->change_type, ['original', 'renewal'], true)) {
            return null;
        }

        $year = (int) $version->data_inicio->format('Y');

        $existing = Nr1Cycle::query()
            ->where('opened_by_contract_version_id', $version->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $sameYear = Nr1Cycle::query()
            ->where('contract_id', $contract->id)
            ->where('reference_year', $year)
            ->first();

        if ($sameYear) {
            // A renovação contratual pode ocorrer dentro de um ano que já possui
            // ciclo NR-1. Nesse caso, preservamos o ciclo existente e o próximo
            // ciclo deverá ser aberto manualmente no ano de referência adequado.
            return null;
        }

        return Nr1Cycle::query()->create([
                'client_id' => $contract->client_id,
                'contract_id' => $contract->id,
                'opened_by_contract_version_id' => $version->id,
                'reference_year' => $year,
                'status' => 'pendente',
                'checklist' => [],
                'migrated_from_client' => false,
                'created_by' => $userId ?? $contract->created_by,
            ]);
    }

    public function createManually(Contract $contract, int $year, int $userId): Nr1Cycle
    {
        if ($contract->tipo_servico !== 'nr1') {
            throw ValidationException::withMessages([
                'reference_year' => 'Ciclos NR-1 só podem ser criados para contratos NR-1.',
            ]);
        }

        if ($contract->nr1Cycles()->where('reference_year', $year)->exists()) {
            throw ValidationException::withMessages([
                'reference_year' => "Já existe uma NR-1/{$year} para este contrato.",
            ]);
        }

        $currentVersion = $contract->currentVersion;
        $versionAlreadyOpenedCycle = $currentVersion?->openedNr1Cycle()->exists() ?? false;

        return $contract->nr1Cycles()->create([
            'client_id' => $contract->client_id,
            'opened_by_contract_version_id' => $versionAlreadyOpenedCycle ? null : $currentVersion?->id,
            'reference_year' => $year,
            'status' => 'pendente',
            'checklist' => [],
            'migrated_from_client' => false,
            'created_by' => $userId,
        ]);
    }
}
