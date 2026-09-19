<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SatisfactionQuestion extends Model
{
    use SoftDeletes;

    protected $fillable = ['description', 'answer_type', 'is_visible', 'is_required', 'display_order'];

    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'is_required' => 'boolean'];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurvey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SatisfactionAnswer::class, 'question_id');
    }
}
