<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BankStatementEntryEvent extends Model
{
    protected $fillable = ['bank_statement_entry_id', 'action', 'reason', 'metadata', 'created_by'];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function entry(): BelongsTo
    {
        return $this->belongsTo(BankStatementEntry::class, 'bank_statement_entry_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
