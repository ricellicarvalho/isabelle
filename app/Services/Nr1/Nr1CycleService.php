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
            if ($sameYear->opened_by_contract_version_id === null && $sameYear->migrated_from_client) {
                $sameYear->update(['opened_by_contract_version_id' => $version->id]);

                return $sameYear;
            }

            throw ValidationException::withMessages([
                'data_inicio' => "Já existe uma NR-1/{$year} para este contrato.",
            ]);
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
}
