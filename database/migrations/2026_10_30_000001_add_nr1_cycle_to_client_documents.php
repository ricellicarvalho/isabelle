<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_documents', function (Blueprint $table) {
            $table->foreignId('nr1_cycle_id')
                ->nullable()
                ->after('client_id')
                ->constrained('nr1_cycles')
                ->nullOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE client_documents MODIFY COLUMN tipo ENUM('laudo','foto','relatorio','matriz_risco','certificado','outro','proposta','boleto','nota_fiscal','devolutiva_nr1') NOT NULL DEFAULT 'outro'");
        }
    }

    public function down(): void
    {
        DB::table('client_documents')
            ->where('tipo', 'devolutiva_nr1')
            ->update(['tipo' => 'outro', 'nr1_cycle_id' => null]);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE client_documents MODIFY COLUMN tipo ENUM('laudo','foto','relatorio','matriz_risco','certificado','outro','proposta','boleto','nota_fiscal') NOT NULL DEFAULT 'outro'");
        }

        Schema::table('client_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nr1_cycle_id');
        });
    }
};
