<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('contato_financeiro_nome')->nullable()->after('email');
            $table->string('contato_financeiro_email')->nullable()->after('contato_financeiro_nome');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['contato_financeiro_nome', 'contato_financeiro_email']);
        });
    }
};
