<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfses', function (Blueprint $table) {
            $table->id();

            // Vínculo com o sistema
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('receivable_id')->nullable()->constrained('receivables')->nullOnDelete();

            // Identificação RPS (antes da autorização municipal)
            $table->unsignedBigInteger('numero_rps');
            $table->string('serie_rps', 5)->default('RPS');
            $table->unsignedTinyInteger('tipo_rps')->default(1)->comment('1=RPS, 2=RPSLM, 3=RPS-M');

            // Retorno da Prefeitura
            $table->string('numero', 20)->nullable()->comment('Número da NFSe autorizada pela prefeitura');
            $table->string('chave_dfe', 100)->nullable()->comment('Chave do DF-e (padrão nacional)');
            $table->string('codigo_verificacao', 100)->nullable();
            $table->timestamp('data_emissao')->nullable();
            $table->date('competencia')->nullable();

            // Status
            $table->enum('status', ['pendente', 'processando', 'emitida', 'cancelada', 'erro'])->default('pendente');
            $table->enum('ambiente', ['1', '2'])->default('2')->comment('1=produção, 2=homologação');

            // Dados financeiros
            $table->decimal('valor', 15, 2);
            $table->decimal('aliquota', 5, 2);
            $table->enum('iss_retido', ['1', '2'])->default('2');
            $table->decimal('valor_iss', 15, 2)->nullable();
            $table->string('item_lista_servico', 10);
            $table->text('discriminacao');

            // Documentos retornados pela API (hexadecimal)
            $table->longText('xml')->nullable()->comment('XML da NFSe em hexadecimal');
            $table->longText('pdf')->nullable()->comment('PDF da NFSe em hexadecimal');
            $table->longText('xml_cancelamento')->nullable();

            // Cancelamento
            $table->text('motivo_cancelamento')->nullable();
            $table->string('codigo_cancelamento', 5)->nullable();

            // Controle de tentativas
            $table->unsignedTinyInteger('tentativas')->default(0);
            $table->text('ultimo_erro')->nullable();

            // Auditoria
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['contract_id', 'status']);
            $table->index(['receivable_id', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfses');
    }
};
