<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ContractVersion extends Model
{
    protected $fillable = [
        'contract_id', 'previous_version_id', 'version_number', 'change_type', 'status',
        'client_id', 'category_id', 'numero', 'tipo_servico', 'descricao', 'valor_total',
        'forma_pagamento', 'quantidade_parcelas', 'data_inicio', 'data_fim', 'arquivo_pdf',
        'observacoes', 'change_reason', 'activated_at', 'created_by', 'activated_by',
    ];

    protected function casts(): array
    {
        return [
            'valor_total' => 'decimal:2',
            'data_inicio' => 'date',
            'data_fim' => 'date',
            'activated_at' => 'datetime',
            'version_number' => 'integer',
        ];
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
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

    public function activatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function changes(): HasMany
    {
        return $this->hasMany(ContractVersionChange::class);
    }

    public function receivables(): HasMany
    {
        return $this->hasMany(Receivable::class);
    }

    public function nfses(): HasMany
    {
        return $this->hasMany(Nfse::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
