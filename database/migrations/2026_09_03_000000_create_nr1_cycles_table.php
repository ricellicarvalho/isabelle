<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nr1_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('contract_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('opened_by_contract_version_id')->nullable()->constrained('contract_versions')->nullOnDelete();
            $table->unsignedSmallInteger('reference_year');
            $table->string('status', 30)->default('pendente');
            $table->json('checklist')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('migrated_from_client')->default(false);
            $table->foreignId('legacy_client_id')->nullable()->unique()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('deleted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['contract_id', 'reference_year']);
            $table->unique('opened_by_contract_version_id');
            $table->index(['client_id', 'reference_year']);
            $table->index(['client_id', 'status']);
        });

        $this->backfillLegacyClients();
    }

    private function backfillLegacyClients(): void
    {
        DB::table('clients')->orderBy('id')->chunkById(100, function ($clients): void {
            foreach ($clients as $client) {
                if (DB::table('nr1_cycles')->where('legacy_client_id', $client->id)->exists()) {
                    continue;
                }

                $checklist = $this->decodeChecklist($client->nr1_checklist);
                $yearFromChecklist = $this->yearFromChecklist($checklist);
                $contractContext = $this->unambiguousContractContext((int) $client->id, $yearFromChecklist);
                $referenceYear = $yearFromChecklist
                    ?? $contractContext['reference_year']
                    ?? (int) substr((string) ($client->created_at ?? now()->toDateString()), 0, 4);

                DB::table('nr1_cycles')->insert([
                    'client_id' => $client->id,
                    'contract_id' => $contractContext['contract_id'],
                    'opened_by_contract_version_id' => $contractContext['version_id'],
                    'reference_year' => $referenceYear,
                    'status' => $client->nr1_status ?: 'pendente',
                    'checklist' => json_encode($checklist, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'notes' => null,
                    'migrated_from_client' => true,
                    'legacy_client_id' => $client->id,
                    'created_by' => $client->created_by,
                    'updated_by' => null,
                    'deleted_by' => null,
                    'created_at' => $client->created_at ?? now(),
                    'updated_at' => $client->updated_at ?? now(),
                    'deleted_at' => null,
                ]);
            }
        });
    }

    private function decodeChecklist(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) ($value ?? '{}'), true);

        return is_array($decoded) ? $decoded : [];
    }

    private function yearFromChecklist(array $checklist): ?int
    {
        $years = [];

        foreach (['etapa1_data', 'etapa2_data', 'etapa3_data', 'etapa4_data', 'etapa5_data'] as $key) {
            if (preg_match('/^(19|20)\d{2}/', (string) ($checklist[$key] ?? ''), $matches)) {
                $years[] = (int) $matches[0];
            }
        }

        if ($years === []) {
            return null;
        }

        $frequencies = array_count_values($years);
        arsort($frequencies);

        return (int) array_key_first($frequencies);
    }

    /**
     * A legacy checklist is linked automatically only when one logical NR-1
     * contract exists. Ambiguous records remain safely linked to their client.
     *
     * @return array{contract_id: ?int, version_id: ?int, reference_year: ?int}
     */
    private function unambiguousContractContext(int $clientId, ?int $preferredYear): array
    {
        $contracts = DB::table('contracts')
            ->where('client_id', $clientId)
            ->where('tipo_servico', 'nr1')
            ->orderBy('id')
            ->pluck('id');

        if ($contracts->count() !== 1) {
            return ['contract_id' => null, 'version_id' => null, 'reference_year' => null];
        }

        $contractId = (int) $contracts->first();
        $versions = DB::table('contract_versions')
            ->where('contract_id', $contractId)
            ->whereIn('change_type', ['original', 'renewal'])
            ->orderBy('version_number')
            ->get(['id', 'data_inicio', 'status']);

        $version = $preferredYear
            ? $versions->first(fn ($item): bool => (int) substr((string) $item->data_inicio, 0, 4) === $preferredYear)
            : null;
        $version ??= $versions->firstWhere('status', 'active') ?? $versions->last();

        return [
            'contract_id' => $contractId,
            'version_id' => $version ? (int) $version->id : null,
            'reference_year' => $version ? (int) substr((string) $version->data_inicio, 0, 4) : null,
        ];
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Rollback bloqueado para proteger o histórico NR-1. Reverta apenas o código; a tabela nr1_cycles deve ser preservada.',
        );
    }
};
