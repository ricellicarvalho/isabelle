<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_remessas', function (Blueprint $table) {
            $table->timestamp('arquivo_baixado_at')->nullable()->after('status');
            $table->foreignId('arquivo_baixado_by')->nullable()->after('arquivo_baixado_at')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('bank_remessas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('arquivo_baixado_by');
            $table->dropColumn('arquivo_baixado_at');
        });
    }
};
