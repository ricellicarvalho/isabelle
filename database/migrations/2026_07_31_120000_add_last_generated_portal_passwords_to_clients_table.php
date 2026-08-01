<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->text('portal_last_generated_password')
                ->nullable()
                ->after('portal_user_id');

            $table->text('portal_financeiro_last_generated_password')
                ->nullable()
                ->after('portal_financeiro_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'portal_last_generated_password',
                'portal_financeiro_last_generated_password',
            ]);
        });
    }
};
