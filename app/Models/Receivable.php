<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Receivable extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'contract_id',
        'contract_version_id',
        'category_id',
        'descricao',
        'valor',
        'data_vencimento',
        'data_pagamento',
        'valor_pago',
        'forma_pagamento',
        'numero_parcela',
        'status',
        'observacoes',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'valor' => 'decimal:2',
            'valor_pago' => 'decimal:2',
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Receivable $receivable): void {
            if ($receivable->contract_id && ! $receivable->contract_version_id) {
                $receivable->contract_version_id = Contract::find($receivable->contract_id)?->current_version_id;
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function contractVersion(): BelongsTo
    {
        return $this->belongsTo(ContractVersion::class);
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

    public function bankBoletos(): HasMany
    {
        return $this->hasMany(BankBoleto::class);
    }

    public function nfses(): HasMany
    {
        return $this->hasMany(\App\Models\Nfse::class);
    }
}
