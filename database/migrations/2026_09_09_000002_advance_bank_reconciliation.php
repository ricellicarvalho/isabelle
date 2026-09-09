<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->decimal('ledger_balance', 14, 2)->nullable();
            $table->dateTime('ledger_balance_at')->nullable();
            $table->string('bank_id', 10)->nullable();
            $table->string('account_number', 50)->nullable();
        });
        Schema::table('bank_statement_entries', function (Blueprint $table) {
            $table->decimal('bank_balance', 14, 2)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('archived_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('archive_reason')->nullable();
        });
        Schema::table('bank_reconciliation_allocations', function (Blueprint $table) {
            // MySQL may use the composite unique index to support this foreign key.
            $table->index('bank_statement_entry_id', 'reconciliation_entry_fk');
            $table->timestamp('reversed_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->index(['bank_statement_entry_id', 'reversed_at'], 'reconciliation_entry_active');
            $table->index(['bank_movement_id', 'reversed_at'], 'reconciliation_movement_active');
        });
        Schema::table('bank_reconciliation_allocations', function (Blueprint $table) {
            $table->dropUnique('bank_reconciliation_unique');
        });
        Schema::create('bank_statement_entry_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_entry_id')->constrained()->cascadeOnDelete();
            $table->string('action', 30);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_statement_entry_events');
        // A rollback discards the reversal history introduced by this migration.
        DB::table('bank_reconciliation_allocations')->whereNotNull('reversed_at')->delete();
        Schema::table('bank_reconciliation_allocations', function (Blueprint $table) {
            $table->unique(['bank_statement_entry_id', 'bank_movement_id'], 'bank_reconciliation_unique');
        });
        Schema::table('bank_reconciliation_allocations', function (Blueprint $table) {
            $table->dropIndex('reconciliation_entry_active');
            $table->dropIndex('reconciliation_movement_active');
            $table->dropIndex('reconciliation_entry_fk');
            $table->dropConstrainedForeignId('reversed_by');
            $table->dropColumn(['reversed_at', 'reversal_reason']);
        });
        Schema::table('bank_statement_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn(['bank_balance', 'archived_at', 'archive_reason']);
        });
        Schema::table('bank_statement_imports', function (Blueprint $table) {
            $table->dropColumn(['ledger_balance', 'ledger_balance_at', 'bank_id', 'account_number']);
        });
    }
};
