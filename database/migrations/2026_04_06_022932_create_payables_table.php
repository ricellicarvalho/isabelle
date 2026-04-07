<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained();
            $table->string('fornecedor')->nullable();
            $table->string('descricao');
            $table->decimal('valor', 10, 2);
            $table->date('data_vencimento');
            $table->date('data_pagamento')->nullable();
            $table->decimal('valor_pago', 10, 2)->nullable();
            $table->enum('forma_pagamento', ['boleto', 'pix', 'transferencia', 'dinheiro', 'cartao'])->nullable();
            $table->enum('status', ['pendente', 'pago', 'cancelado', 'vencido'])->default('pendente');
            $table->text('observacoes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
