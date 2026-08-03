<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payable_recurrences', function (Blueprint $table) {
            $table->id();
            $table->string('frequency')->default('monthly');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->unsignedSmallInteger('occurrences_count');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::table('payables', function (Blueprint $table) {
            $table->foreignId('payable_recurrence_id')
                ->nullable()
                ->after('id')
                ->constrained('payable_recurrences')
                ->nullOnDelete();
            $table->unsignedSmallInteger('recurrence_sequence')->nullable()->after('payable_recurrence_id');
            $table->unsignedSmallInteger('recurrence_total')->nullable()->after('recurrence_sequence');
        });
    }

    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payable_recurrence_id');
            $table->dropColumn(['recurrence_sequence', 'recurrence_total']);
        });

        Schema::dropIfExists('payable_recurrences');
    }
};
