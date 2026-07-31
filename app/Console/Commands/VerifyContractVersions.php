<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\Nfse;
use App\Models\Receivable;
use Illuminate\Console\Command;

class VerifyContractVersions extends Command
{
    protected $signature = 'contracts:verify-version-migration';

    protected $description = 'Valida, sem alterar dados, os vínculos criados pela migração de versões dos contratos';

    public function handle(): int
    {
        $contracts = Contract::count();
        $versions = ContractVersion::count();
        $withoutCurrent = Contract::whereNull('current_version_id')->count();
        $withoutInitial = Contract::whereDoesntHave('versions')->count();
        $orphanReceivables = Receivable::whereNotNull('contract_id')->whereNull('contract_version_id')->count();
        $orphanNfses = Nfse::whereNotNull('contract_id')->whereNull('contract_version_id')->count();

        $this->table(['Verificação', 'Quantidade'], [
            ['Contratos', $contracts],
            ['Versões', $versions],
            ['Contratos sem versão vigente', $withoutCurrent],
            ['Contratos sem versão inicial', $withoutInitial],
            ['Parcelas sem versão', $orphanReceivables],
            ['NFSe sem versão', $orphanNfses],
        ]);

        $errors = $withoutCurrent + $withoutInitial + $orphanReceivables + $orphanNfses;
        if ($errors > 0 || $versions < $contracts) {
            $this->error('Foram encontradas inconsistências na migração de versões.');

            return self::FAILURE;
        }

        $this->info('Migração de versões validada sem inconsistências.');

        return self::SUCCESS;
    }
}
