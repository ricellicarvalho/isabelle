<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_retornos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nome_arquivo');
            $table->string('caminho_arquivo')->nullable();
            $table->string('banco', 10)->nullable();
            $table->string('layout', 10)->default('400');
            $table->date('data_arquivo')->nullable();
            $table->dateTime('data_processamento');
            $table->unsignedInteger('quantidade_titulos')->default(0);
            $table->unsignedInteger('quantidade_liquidados')->default(0);
            $table->unsignedInteger('quantidade_baixados')->default(0);
            $table->unsignedInteger('quantidade_entradas')->default(0);
            $table->unsignedInteger('quantidade_alterados')->default(0);
            $table->unsignedInteger('quantidade_erros')->default(0);
            $table->unsignedInteger('quantidade_nao_encontrados')->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->json('log')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('bank_boletos', function (Blueprint $table) {
            $table->foreignId('bank_retorno_id')
                ->nullable()
                ->after('remessa_id')
                ->constrained('bank_retornos')
                ->nullOnDelete();
            $table->date('data_pagamento')->nullable()->after('data_vencimento');
            $table->decimal('valor_pago', 12, 2)->nullable()->after('data_pagamento');
        });
    }

    public function down(): void
    {
        Schema::table('bank_boletos', function (Blueprint $table) {
            $table->dropForeign(['bank_retorno_id']);
            $table->dropColumn(['bank_retorno_id', 'data_pagamento', 'valor_pago']);
        });

        Schema::dropIfExists('bank_retornos');
    }
};
