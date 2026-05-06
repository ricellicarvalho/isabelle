<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NfseLog extends Model
{
    protected $fillable = [
        'nfse_id',
        'acao',
        'request_payload',
        'response_payload',
        'http_status',
        'situacao',
        'mensagem',
    ];

    protected function casts(): array
    {
        return [
            'request_payload'  => 'array',
            'response_payload' => 'array',
            'http_status'      => 'integer',
        ];
    }

    public function nfse(): BelongsTo
    {
        return $this->belongsTo(Nfse::class);
    }
}
