<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    protected $fillable = ['bank_account_id', 'filename', 'file_hash', 'period_start', 'period_end', 'ledger_balance', 'ledger_balance_at', 'bank_id', 'account_number', 'created_by'];

    protected function casts(): array
    {
        return ['period_start' => 'date', 'period_end' => 'date', 'ledger_balance' => 'decimal:2', 'ledger_balance_at' => 'datetime'];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(BankStatementEntry::class);
    }
}
