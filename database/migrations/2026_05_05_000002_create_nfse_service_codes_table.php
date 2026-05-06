<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfse_service_codes', function (Blueprint $table) {
            $table->id();

            $table->string('tipo_servico', 50)->unique()->comment('Corresponde a Contract.tipo_servico');
            $table->string('descricao');
            $table->string('item_lista_servico', 10)->comment('LC 116/2003 (ex: 17.01, 8.02)');
            $table->string('codigo_tributacao_municipio', 20)->nullable();
            $table->string('codigo_cnae', 10)->nullable();
            $table->decimal('aliquota', 5, 2)->default(2.00)->comment('Alíquota ISS (%)');
            $table->boolean('ativo')->default(true);

            // Auditoria
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_service_codes');
    }
};
