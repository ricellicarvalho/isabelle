<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfse_service_codes', function (Blueprint $table) {
            $table->string('codigo_tributacao_nacional', 6)
                ->nullable()
                ->after('codigo_tributacao_municipio')
                ->comment('cTribNac (6 dígitos) — código da lista de serviços nacional SPED/RFB');
        });
    }

    public function down(): void
    {
        Schema::table('nfse_service_codes', function (Blueprint $table) {
            $table->dropColumn('codigo_tributacao_nacional');
        });
    }
};
