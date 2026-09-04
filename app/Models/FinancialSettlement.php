<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FinancialSettlement extends Model
{
    protected $fillable = ['settleable_type', 'settleable_id', 'bank_account_id', 'settled_at', 'amount', 'payment_method', 'reference', 'status', 'origin', 'notes', 'created_by'];

    protected function casts(): array { return ['settled_at' => 'datetime', 'amount' => 'decimal:2']; }

    public function settleable(): MorphTo { return $this->morphTo(); }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function movement() { return $this->hasOne(BankMovement::class); }
}
