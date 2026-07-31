<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractVersionChange extends Model
{
    protected $fillable = ['contract_version_id', 'field', 'old_value', 'new_value', 'created_by'];

    public function contractVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class);
    }
}
