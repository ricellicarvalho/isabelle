<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankMovement extends Model
{
    use SoftDeletes;

    protected static function booted(): void
    {
        $guard = function (self $movement): void {
            if ($movement->financial_settlement_id || $movement->transfer_group || \App\Models\BankReconciliationAllocation::where('bank_movement_id', $movement->id)->whereNull('reversed_at')->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['amount' => 'Movimento vinculado: use o fluxo de estorno ou desfaça a conciliação.']);
            }
        };
        static::updating($guard);
        static::deleting($guard);
    }

    protected $fillable = ['bank_account_id', 'financial_settlement_id', 'category_id', 'transfer_group', 'occurred_at', 'direction', 'amount', 'origin', 'description', 'counterparty', 'reference', 'status', 'notes', 'created_by'];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'amount' => 'decimal:2'];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function settlement(): BelongsTo
    {
        return $this->belongsTo(FinancialSettlement::class, 'financial_settlement_id');
    }

    public function reconciliationAllocations(): HasMany
    {
        return $this->hasMany(BankReconciliationAllocation::class);
    }

    public function activeReconciliationAllocations(): HasMany
    {
        return $this->reconciliationAllocations()->whereNull('reversed_at');
    }
}
