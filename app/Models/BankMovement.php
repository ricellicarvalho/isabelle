<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankMovement extends Model
{
    use SoftDeletes;

    protected $fillable = ['bank_account_id', 'financial_settlement_id', 'category_id', 'transfer_group', 'occurred_at', 'direction', 'amount', 'origin', 'description', 'counterparty', 'reference', 'status', 'notes', 'created_by'];

    protected function casts(): array { return ['occurred_at' => 'datetime', 'amount' => 'decimal:2']; }

    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function settlement(): BelongsTo { return $this->belongsTo(FinancialSettlement::class, 'financial_settlement_id'); }
}
