<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_boletos', function (Blueprint $table) {
            $table->string('registro_bancario_status', 20)->default('pendente')->after('status');
            $table->string('registro_bancario_motivo')->nullable()->after('registro_bancario_status');
        });

        Schema::table('bank_remessas', function (Blueprint $table) {
            $table->string('resultado_bancario', 30)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('bank_boletos', function (Blueprint $table) {
            $table->dropColumn(['registro_bancario_status', 'registro_bancario_motivo']);
        });

        Schema::table('bank_remessas', function (Blueprint $table) {
            $table->dropColumn('resultado_bancario');
        });
    }
};
