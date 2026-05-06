<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfse_configs', function (Blueprint $table) {
            $table->enum('responsavel_retencao', ['1', '2', '3'])
                ->default('2')
                ->after('iss_retido')
                ->comment('Responsável pela retenção do ISS: 1=Emitente, 2=Tomador, 3=Intermediário. Enviado somente quando iss_retido=1');
        });
    }

    public function down(): void
    {
        Schema::table('nfse_configs', function (Blueprint $table) {
            $table->dropColumn('responsavel_retencao');
        });
    }
};
