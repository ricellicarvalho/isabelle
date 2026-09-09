<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_settlements', function (Blueprint $table) {
            $table->decimal('principal_amount', 14, 2)->nullable();
            foreach (['interest', 'penalty', 'discount', 'fee'] as $field) {
                $table->decimal($field, 14, 2)->default(0);
            }
            $table->string('idempotency_key')->nullable()->unique();
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reversal_reason')->nullable();
        });
        DB::table('financial_settlements')->update(['principal_amount' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('financial_settlements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['principal_amount', 'interest', 'penalty', 'discount', 'fee', 'idempotency_key', 'reversed_at', 'reversal_reason']);
        });
    }
};
