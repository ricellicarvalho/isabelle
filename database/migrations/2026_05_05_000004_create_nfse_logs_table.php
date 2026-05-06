<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nfse_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('nfse_id')->nullable()->constrained('nfses')->nullOnDelete();
            $table->string('acao', 50)->comment('emitir|cancelar|consultar');
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->string('situacao', 20)->nullable()->comment('sucesso|erro');
            $table->text('mensagem')->nullable();

            $table->timestamps();

            $table->index('nfse_id');
            $table->index('acao');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nfse_logs');
    }
};
