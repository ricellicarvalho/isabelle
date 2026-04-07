<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained();
            $table->foreignId('contract_id')->nullable()->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->enum('tipo', ['avaliacao_nr1', 'devolutiva', 'treinamento', 'palestra', 'reuniao', 'outro'])->default('outro');
            $table->dateTime('data_inicio');
            $table->dateTime('data_fim')->nullable();
            $table->boolean('dia_inteiro')->default(false);
            $table->string('local')->nullable();
            $table->enum('status', ['agendado', 'realizado', 'cancelado'])->default('agendado');
            $table->boolean('bloquear_agenda')->default(false);
            $table->string('cor', 7)->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('deleted_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
