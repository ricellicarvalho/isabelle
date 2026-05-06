<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nfse_configs', function (Blueprint $table) {
            $table->string('usuario_prefeitura')->nullable()->after('ativo');
            $table->string('senha_prefeitura')->nullable()->after('usuario_prefeitura');
            $table->string('frase_secreta')->nullable()->after('senha_prefeitura');
            $table->string('chave_acesso')->nullable()->after('frase_secreta');
            $table->string('chave_autorizacao')->nullable()->after('chave_acesso');
        });
    }

    public function down(): void
    {
        Schema::table('nfse_configs', function (Blueprint $table) {
            $table->dropColumn([
                'usuario_prefeitura',
                'senha_prefeitura',
                'frase_secreta',
                'chave_acesso',
                'chave_autorizacao',
            ]);
        });
    }
};
