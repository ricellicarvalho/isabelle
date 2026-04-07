<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_boletos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained();
            $table->foreignId('remessa_id')->nullable()->constrained('bank_remessas')->nullOnDelete();
            $table->string('nosso_numero')->unique();
            $table->string('numero_documento')->nullable();
            $table->string('carteira', 10)->nullable();
            $table->string('codigo_barras')->nullable();
            $table->string('linha_digitavel')->nullable();
            $table->date('data_vencimento');
            $table->decimal('valor', 10, 2);
            $table->enum('status', ['pendente', 'emitido', 'pago', 'cancelado', 'baixado'])->default('pendente');
            $table->string('instrucao_remessa')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_boletos');
    }
};
