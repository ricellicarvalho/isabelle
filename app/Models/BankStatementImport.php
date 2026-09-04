<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankStatementImport extends Model
{
    protected $fillable = ['bank_account_id', 'filename', 'file_hash', 'period_start', 'period_end', 'created_by'];
    protected function casts(): array { return ['period_start' => 'date', 'period_end' => 'date']; }
    public function bankAccount(): BelongsTo { return $this->belongsTo(BankAccount::class); }
    public function entries(): HasMany { return $this->hasMany(BankStatementEntry::class); }
}
