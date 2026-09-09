<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankReconciliationAllocation extends Model
{
    protected $fillable = ['bank_statement_entry_id', 'bank_movement_id', 'amount', 'created_by', 'reversed_at', 'reversed_by', 'reversal_reason'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'reversed_at' => 'datetime'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(BankStatementEntry::class, 'bank_statement_entry_id');
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(BankMovement::class, 'bank_movement_id');
    }
}
