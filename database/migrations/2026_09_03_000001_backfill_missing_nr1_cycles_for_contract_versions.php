<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('nr1_cycles')) {
            return;
        }

        DB::table('contract_versions')
            ->where('tipo_servico', 'nr1')
            ->whereIn('change_type', ['original', 'renewal'])
            ->orderBy('id')
            ->chunkById(100, function ($versions): void {
                foreach ($versions as $version) {
                    if (DB::table('nr1_cycles')->where('opened_by_contract_version_id', $version->id)->exists()) {
                        continue;
                    }

                    $year = (int) substr((string) $version->data_inicio, 0, 4);
                    $cycleForYear = DB::table('nr1_cycles')
                        ->where('contract_id', $version->contract_id)
                        ->where('reference_year', $year)
                        ->first();

                    if ($cycleForYear) {
                        if ($cycleForYear->opened_by_contract_version_id === null) {
                            DB::table('nr1_cycles')->where('id', $cycleForYear->id)->update([
                                'opened_by_contract_version_id' => $version->id,
                                'updated_at' => now(),
                            ]);
                        }

                        continue;
                    }

                    DB::table('nr1_cycles')->insert([
                        'client_id' => $version->client_id,
                        'contract_id' => $version->contract_id,
                        'opened_by_contract_version_id' => $version->id,
                        'reference_year' => $year,
                        'status' => 'pendente',
                        'checklist' => json_encode([], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'notes' => null,
                        'migrated_from_client' => false,
                        'legacy_client_id' => null,
                        'created_by' => $version->created_by,
                        'updated_by' => null,
                        'deleted_by' => null,
                        'created_at' => $version->created_at ?? now(),
                        'updated_at' => $version->updated_at ?? now(),
                        'deleted_at' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback bloqueado para proteger os checklists NR-1 vinculados às versões contratuais.',
        );
    }
};
