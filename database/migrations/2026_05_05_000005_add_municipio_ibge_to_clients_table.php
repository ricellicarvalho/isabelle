<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('municipio_ibge', 7)
                ->nullable()
                ->after('cidade')
                ->comment('Código IBGE 7 dígitos — obrigatório para emissão de NFSe');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('municipio_ibge');
        });
    }
};
