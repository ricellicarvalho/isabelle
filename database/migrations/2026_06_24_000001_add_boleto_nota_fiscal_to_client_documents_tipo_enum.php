<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE client_documents MODIFY COLUMN tipo ENUM('laudo','foto','relatorio','matriz_risco','certificado','outro','proposta','boleto','nota_fiscal') NOT NULL DEFAULT 'outro'");
    }

    public function down(): void
    {
        DB::statement("UPDATE client_documents SET tipo = 'outro' WHERE tipo IN ('boleto','nota_fiscal')");
        DB::statement("ALTER TABLE client_documents MODIFY COLUMN tipo ENUM('laudo','foto','relatorio','matriz_risco','certificado','outro','proposta') NOT NULL DEFAULT 'outro'");
    }
};
