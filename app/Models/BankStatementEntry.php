<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementEntry extends Model
{
    protected $fillable = ['bank_statement_import_id', 'bank_account_id', 'fit_id', 'occurred_at', 'amount', 'type', 'document', 'memo', 'status', 'bank_balance', 'archived_at', 'archived_by', 'archive_reason'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'amount' => 'decimal:2', 'bank_balance' => 'decimal:2', 'archived_at' => 'datetime'];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BankReconciliationAllocation::class);
    }

    public function activeAllocations(): HasMany
    {
        return $this->allocations()->whereNull('reversed_at');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BankStatementEntryEvent::class);
    }
}
