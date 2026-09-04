<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The guards also make this migration safe to resume after an interrupted
        // MySQL DDL operation (ALTER TABLE statements are committed immediately).
        if (! Schema::hasColumn('bank_accounts', 'nome')) {
            Schema::table('bank_accounts', function (Blueprint $table) {
                $table->string('nome')->nullable()->after('id');
                $table->string('tipo', 30)->default('conta_corrente')->after('descricao');
                $table->date('opening_balance_date')->nullable()->after('tipo');
                $table->decimal('opening_balance', 14, 2)->default(0)->after('opening_balance_date');
                $table->decimal('credit_limit', 14, 2)->default(0)->after('opening_balance');
                $table->boolean('uses_billing')->default(false)->after('credit_limit');
                $table->boolean('is_default_billing')->default(false)->after('uses_billing');
            });
        }

        DB::table('bank_accounts')->whereNotNull('carteira')->update(['uses_billing' => true, 'is_default_billing' => true]);
        DB::table('bank_accounts')->whereNull('nome')->update([
            'nome' => DB::raw("COALESCE(descricao, CONCAT('Banco ', banco, ' · ', conta))"),
            'opening_balance_date' => '2026-07-31',
        ]);
        DB::table('bank_accounts')
            ->where('banco', '237')
            ->where('agencia', '0590')
            ->where('conta', '88942')
            ->update(['opening_balance_date' => '2026-07-31', 'opening_balance' => 80.01]);

        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->string('carteira', 10)->nullable()->change();
            $table->string('cedente_nome')->nullable()->change();
            $table->string('cedente_documento', 18)->nullable()->change();
            $table->string('layout_remessa', 3)->nullable()->change();
        });

        $firstUserId = DB::table('users')->orderBy('id')->value('id');
        if ($firstUserId && ! DB::table('bank_accounts')->where('banco', '756')->where('agencia', '5004')->where('conta', '1161429')->exists()) {
            DB::table('bank_accounts')->insert([
                'nome' => 'Sicoob Movimento', 'banco' => '756', 'descricao' => 'Sicoob Movimento',
                'tipo' => 'conta_corrente', 'agencia' => '5004', 'conta' => '1161429', 'conta_dv' => '3',
                'opening_balance_date' => '2026-07-31', 'opening_balance' => 4221.34, 'credit_limit' => 0,
                'uses_billing' => false, 'is_default_billing' => false, 'ativo' => true,
                'proximo_nosso_numero' => 1, 'proximo_sequencial_remessa' => 1,
                'created_by' => $firstUserId, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        foreach (['payables', 'receivables'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'bank_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('bank_account_id')->nullable()->constrained()->nullOnDelete();
                });
            }
        }

        if (! Schema::hasTable('financial_settlements')) Schema::create('financial_settlements', function (Blueprint $table) {
            $table->id();
            $table->morphs('settleable');
            $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();
            $table->dateTime('settled_at');
            $table->decimal('amount', 14, 2);
            $table->string('payment_method', 30)->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->string('origin', 30)->default('manual');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        if (! Schema::hasTable('bank_movements')) Schema::create('bank_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_settlement_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('transfer_group')->nullable()->index();
            $table->dateTime('occurred_at')->index();
            $table->string('direction', 10);
            $table->decimal('amount', 14, 2);
            $table->string('origin', 30);
            $table->string('description');
            $table->string('counterparty')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('confirmed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['bank_account_id', 'occurred_at', 'status'], 'bank_movement_account_date_status');
        });

        if (! Schema::hasTable('bank_statement_imports')) Schema::create('bank_statement_imports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->char('file_hash', 64);
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['bank_account_id', 'file_hash']);
        });

        if (! Schema::hasTable('bank_statement_entries')) Schema::create('bank_statement_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_import_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained()->cascadeOnDelete();
            $table->string('fit_id')->nullable();
            $table->dateTime('occurred_at')->index();
            $table->decimal('amount', 14, 2);
            $table->string('type', 20)->nullable();
            $table->string('document')->nullable();
            $table->text('memo')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->unique(['bank_account_id', 'fit_id']);
        });

        if (! Schema::hasTable('bank_reconciliation_allocations')) Schema::create('bank_reconciliation_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_statement_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_movement_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 14, 2);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['bank_statement_entry_id', 'bank_movement_id'], 'bank_reconciliation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_allocations');
        Schema::dropIfExists('bank_statement_entries');
        Schema::dropIfExists('bank_statement_imports');
        Schema::dropIfExists('bank_movements');
        Schema::dropIfExists('financial_settlements');
        foreach (['payables', 'receivables'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropConstrainedForeignId('bank_account_id'));
        }
        Schema::table('bank_accounts', function (Blueprint $table) {
            $table->dropColumn(['nome', 'tipo', 'opening_balance_date', 'opening_balance', 'credit_limit', 'uses_billing', 'is_default_billing']);
        });
    }
};
