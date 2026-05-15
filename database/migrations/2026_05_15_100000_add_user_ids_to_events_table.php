<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('user_ids')->nullable()->after('user_id');
        });

        // Migra dados existentes: popula user_ids com o user_id atual
        DB::table('events')->whereNotNull('user_id')->update([
            'user_ids' => DB::raw("JSON_ARRAY(user_id)"),
        ]);
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('user_ids');
        });
    }
};
