<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('cadastro_token', 64)->nullable()->unique()->after('observacoes');
            $table->timestamp('cadastro_token_expira_em')->nullable()->after('cadastro_token');
            $table->boolean('cadastro_preenchido')->default(false)->after('cadastro_token_expira_em');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['cadastro_token', 'cadastro_token_expira_em', 'cadastro_preenchido']);
        });
    }
};
