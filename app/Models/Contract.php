<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contract extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'category_id',
        'numero',
        'tipo_servico',
        'descricao',
        'valor_total',
        'forma_pagamento',
        'quantidade_parcelas',
        'data_inicio',
        'data_fim',
        'status',
        'arquivo_pdf',
        'observacoes',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'data_inicio' => 'date',
            'data_fim' => 'date',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function nfses(): HasMany
    {
        return $this->hasMany(\App\Models\Nfse::class);
    }
}
