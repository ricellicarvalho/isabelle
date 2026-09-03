<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Nr1Cycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class AuditNr1Cycles extends Command
{
    protected $signature = 'nr1:audit-cycles';

    protected $description = 'Valida que todas as NR-1 legadas foram preservadas nos ciclos anuais';

    public function handle(): int
    {
        if (! Schema::hasTable('nr1_cycles')) {
            $this->error('A tabela nr1_cycles ainda não existe.');

            return self::FAILURE;
        }

        $legacyCycles = Nr1Cycle::withTrashed()->whereNotNull('legacy_client_id')->get();
        $legacyMaxClientId = (int) $legacyCycles->max('legacy_client_id');
        $clients = Client::withTrashed()->where('id', '<=', $legacyMaxClientId)->get();
        $cycles = $legacyCycles->keyBy('legacy_client_id');
        $failures = [];

        foreach ($clients as $client) {
            $cycle = $cycles->get($client->id);

            if (! $cycle) {
                $failures[] = "Cliente {$client->id}: ciclo legado ausente";
                continue;
            }

            if ((int) $cycle->client_id !== (int) $client->id) {
                $failures[] = "Cliente {$client->id}: vínculo de cliente divergente";
            }

            if (($cycle->checklist ?? []) !== ($client->nr1_checklist ?? [])) {
                $failures[] = "Cliente {$client->id}: checklist divergente";
            }

            if ($cycle->status !== $client->nr1_status) {
                $failures[] = "Cliente {$client->id}: status divergente";
            }
        }

        $this->table(['Métrica', 'Quantidade'], [
            ['Clientes legados', $clients->count()],
            ['Ciclos totais', Nr1Cycle::withTrashed()->count()],
            ['Ciclos migrados', Nr1Cycle::withTrashed()->where('migrated_from_client', true)->count()],
            ['Com contrato', Nr1Cycle::withTrashed()->whereNotNull('contract_id')->count()],
            ['Vínculo contratual pendente', Nr1Cycle::withTrashed()->whereNull('contract_id')->count()],
            ['Divergências', count($failures)],
        ]);

        foreach ($failures as $failure) {
            $this->error($failure);
        }

        if ($failures !== []) {
            return self::FAILURE;
        }

        $this->info('Auditoria concluída: todas as NR-1 legadas estão preservadas e vinculadas aos clientes corretos.');

        return self::SUCCESS;
    }
}
