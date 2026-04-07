<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('titulo');
            $table->enum('tipo', ['laudo', 'foto', 'relatorio', 'matriz_risco', 'certificado', 'outro'])->default('outro');
            $table->string('caminho_arquivo');
            $table->text('descricao')->nullable();
            $table->boolean('visivel_portal')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_documents');
    }
};
