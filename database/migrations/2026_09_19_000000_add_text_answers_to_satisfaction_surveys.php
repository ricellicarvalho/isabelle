<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('satisfaction_questions', function (Blueprint $table): void {
            $table->string('answer_type', 20)->default('scale');
        });

        Schema::table('satisfaction_answers', function (Blueprint $table): void {
            $table->unsignedTinyInteger('numeric_value')->nullable()->change();
            $table->text('text_value')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('satisfaction_answers', function (Blueprint $table): void {
            $table->dropColumn('text_value');
            $table->unsignedTinyInteger('numeric_value')->nullable(false)->change();
        });

        Schema::table('satisfaction_questions', function (Blueprint $table): void {
            $table->dropColumn('answer_type');
        });
    }
};
