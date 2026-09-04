<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BankReconciliationAllocation extends Model
{
    protected $fillable = ['bank_statement_entry_id', 'bank_movement_id', 'amount', 'created_by'];
    protected function casts(): array { return ['amount' => 'decimal:2']; }
}
