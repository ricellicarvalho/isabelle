<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SatisfactionAnswer extends Model
{
    protected $fillable = ['question_id', 'numeric_value', 'text_value', 'question_snapshot', 'response_type_snapshot'];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSubmission::class, 'submission_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(SatisfactionQuestion::class, 'question_id');
    }
}
