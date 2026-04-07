<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained();
            $table->string('numero')->unique();
            $table->enum('tipo_servico', ['nr1', 'palestra', 'consultoria', 'treinamento', 'outro'])->default('nr1');
            $table->text('descricao')->nullable();
            $table->decimal('valor_total', 10, 2);
            $table->enum('forma_pagamento', ['boleto', 'pix', 'transferencia', 'dinheiro', 'cartao'])->default('boleto');
            $table->unsignedInteger('quantidade_parcelas')->default(1);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->enum('status', ['rascunho', 'ativo', 'finalizado', 'cancelado'])->default('rascunho');
            $table->string('arquivo_pdf')->nullable();
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
