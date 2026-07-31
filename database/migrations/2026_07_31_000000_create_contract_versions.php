<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('previous_version_id')->nullable()->constrained('contract_versions')->nullOnDelete();
            $table->unsignedInteger('version_number');
            $table->enum('change_type', ['original', 'renewal', 'amendment', 'correction'])->default('original');
            $table->enum('status', ['draft', 'active', 'superseded', 'cancelled'])->default('active');
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('numero');
            $table->enum('tipo_servico', ['nr1', 'palestra', 'consultoria', 'treinamento', 'outro'])->default('nr1');
            $table->text('descricao')->nullable();
            $table->decimal('valor_total', 10, 2);
            $table->enum('forma_pagamento', ['boleto', 'pix', 'transferencia', 'dinheiro', 'cartao'])->default('boleto');
            $table->unsignedInteger('quantidade_parcelas')->default(1);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->string('arquivo_pdf')->nullable();
            $table->text('observacoes')->nullable();
            $table->text('change_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('activated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['contract_id', 'version_number']);
            $table->index(['contract_id', 'status']);
            $table->index(['status', 'data_fim']);
        });

        Schema::create('contract_version_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_version_id')->constrained()->cascadeOnDelete();
            $table->string('field');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();

            $table->index(['contract_version_id', 'field']);
        });

        Schema::table('contracts', function (Blueprint $table) {
            $table->unsignedBigInteger('current_version_id')->nullable()->after('id');
        });

        foreach (['receivables', 'nfses', 'events'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->unsignedBigInteger('contract_version_id')->nullable()->after('contract_id');
            });
        }

        DB::table('contracts')->orderBy('id')->chunkById(100, function ($contracts): void {
            foreach ($contracts as $contract) {
                $versionId = DB::table('contract_versions')->insertGetId([
                    'contract_id' => $contract->id,
                    'previous_version_id' => null,
                    'version_number' => 1,
                    'change_type' => 'original',
                    'status' => match ($contract->status) {
                        'rascunho' => 'draft',
                        'cancelado' => 'cancelled',
                        default => 'active',
                    },
                    'client_id' => $contract->client_id,
                    'category_id' => $contract->category_id,
                    'numero' => $contract->numero,
                    'tipo_servico' => $contract->tipo_servico,
                    'descricao' => $contract->descricao,
                    'valor_total' => $contract->valor_total,
                    'forma_pagamento' => $contract->forma_pagamento,
                    'quantidade_parcelas' => $contract->quantidade_parcelas,
                    'data_inicio' => $contract->data_inicio,
                    'data_fim' => $contract->data_fim,
                    'arquivo_pdf' => $contract->arquivo_pdf,
                    'observacoes' => $contract->observacoes,
                    'change_reason' => 'Versão inicial criada automaticamente na implantação do histórico de renovações.',
                    'activated_at' => $contract->created_at,
                    'created_by' => $contract->created_by,
                    'activated_by' => $contract->created_by,
                    'created_at' => $contract->created_at,
                    'updated_at' => $contract->updated_at,
                ]);

                DB::table('contracts')->where('id', $contract->id)->update(['current_version_id' => $versionId]);

                foreach (['receivables', 'nfses', 'events'] as $tableName) {
                    DB::table($tableName)
                        ->where('contract_id', $contract->id)
                        ->whereNull('contract_version_id')
                        ->update(['contract_version_id' => $versionId]);
                }
            }
        }, 'id');

        Schema::table('contracts', function (Blueprint $table) {
            $table->foreign('current_version_id')->references('id')->on('contract_versions')->nullOnDelete();
        });

        foreach (['receivables', 'nfses', 'events'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('contract_version_id')->references('id')->on('contract_versions')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['current_version_id']);
            $table->dropColumn('current_version_id');
        });

        foreach (['receivables', 'nfses', 'events'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['contract_version_id']);
                $table->dropColumn('contract_version_id');
            });
        }

        Schema::dropIfExists('contract_version_changes');
        Schema::dropIfExists('contract_versions');
    }
};
