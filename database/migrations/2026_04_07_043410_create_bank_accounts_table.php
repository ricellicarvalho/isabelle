<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('banco', 3)->comment('Código FEBRABAN (237, 341, 001, 104, 033)');
            $table->string('descricao')->nullable();
            $table->string('agencia', 10);
            $table->string('agencia_dv', 2)->nullable();
            $table->string('conta', 20);
            $table->string('conta_dv', 2)->nullable();
            $table->string('carteira', 10);
            $table->string('convenio', 30)->nullable();
            $table->string('cedente_nome');
            $table->string('cedente_documento', 18);
            $table->string('cedente_endereco')->nullable();
            $table->string('cedente_cidade_uf')->nullable();
            $table->string('layout_remessa', 3)->default('400');
            $table->unsignedBigInteger('proximo_nosso_numero')->default(1);
            $table->unsignedInteger('proximo_sequencial_remessa')->default(1);
            $table->boolean('ativo')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
