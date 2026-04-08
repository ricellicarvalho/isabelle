<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class NotificationLog extends Model
{
    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'alert_type',
        'days_before',
        'sent_date',
    ];

    protected function casts(): array
    {
        return [
            'sent_date' => 'date',
            'days_before' => 'integer',
        ];
    }

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }
}
