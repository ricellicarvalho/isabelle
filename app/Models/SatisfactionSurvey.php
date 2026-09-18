<?php

namespace App\Models;

use App\Enums\SurveyResponseType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class SatisfactionSurvey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'description', 'introduction', 'response_type', 'is_active', 'starts_at', 'ends_at',
        'thank_you_message', 'allow_multiple_submissions', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'response_type' => SurveyResponseType::class,
            'is_active' => 'boolean',
            'allow_multiple_submissions' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $survey): void {
            $survey->public_token ??= (string) Str::uuid();
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_token';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        return $this->where($field ?? 'public_token', $value)->first();
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SatisfactionQuestion::class, 'survey_id')->orderBy('display_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(SatisfactionSubmission::class, 'survey_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->whereHas('questions', fn (Builder $query) => $query->where('is_visible', true));
    }

    public function isPublished(): bool
    {
        return $this->is_active
            && now()->between($this->starts_at, $this->ends_at)
            && $this->questions()->where('is_visible', true)->exists();
    }

    public function statusLabel(): string
    {
        if (! $this->is_active) {
            return 'Inativa';
        }
        if (now()->lt($this->starts_at)) {
            return 'Agendada';
        }
        if (now()->gt($this->ends_at)) {
            return 'Encerrada';
        }
        if (! $this->questions()->where('is_visible', true)->exists()) {
            return 'Sem perguntas';
        }

        return 'No ar';
    }

    public function publicUrl(): string
    {
        $domain = config('panels.portal_domain');
        $port = parse_url(config('app.url'), PHP_URL_PORT);
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?: 'https';
        $host = $domain ? $domain.($port ? ':'.$port : '') : null;

        $url = route('public.surveys.show', $this, absolute: true);

        return $host ? $scheme.'://'.$host.parse_url($url, PHP_URL_PATH) : $url;
    }
}
