<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SatisfactionSubmission extends Model
{
    protected $fillable = ['public_identifier', 'respondent_fingerprint_hash', 'ip_hash', 'user_agent_hash', 'submitted_at'];

    protected function casts(): array
    {
        return ['submitted_at' => 'datetime'];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(SatisfactionSurvey::class, 'survey_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(SatisfactionAnswer::class, 'submission_id');
    }
}
