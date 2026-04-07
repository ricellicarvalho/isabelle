<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_remessas', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('sequencial_arquivo');
            $table->dateTime('data_geracao');
            $table->string('caminho_arquivo');
            $table->unsignedInteger('quantidade_titulos')->default(0);
            $table->decimal('valor_total', 12, 2)->default(0);
            $table->enum('layout', ['cnab240', 'cnab400'])->default('cnab400');
            $table->enum('status', ['gerado', 'enviado', 'processado'])->default('gerado');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_remessas');
    }
};
