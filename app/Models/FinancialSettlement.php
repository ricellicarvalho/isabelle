<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialSettlement extends Model
{
    protected $fillable = ['settleable_type', 'settleable_id', 'bank_account_id', 'settled_at', 'amount', 'payment_method', 'reference', 'status', 'origin', 'notes', 'created_by', 'principal_amount', 'interest', 'penalty', 'discount', 'fee', 'idempotency_key', 'reversed_at', 'reversed_by', 'reversal_reason'];

    protected function casts(): array
    {
        return ['settled_at' => 'datetime', 'amount' => 'decimal:2', 'principal_amount' => 'decimal:2', 'interest' => 'decimal:2', 'penalty' => 'decimal:2', 'discount' => 'decimal:2', 'fee' => 'decimal:2', 'reversed_at' => 'datetime'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function settleable(): MorphTo
    {
        return $this->morphTo();
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function movement()
    {
        return $this->hasOne(BankMovement::class);
    }
}
