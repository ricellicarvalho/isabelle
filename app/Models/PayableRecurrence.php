<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayableRecurrence extends Model
{
    protected $fillable = [
        'frequency',
        'starts_at',
        'ends_at',
        'occurrences_count',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'date',
            'ends_at' => 'date',
            'occurrences_count' => 'integer',
        ];
    }

    public function payables(): HasMany
    {
        return $this->hasMany(Payable::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
