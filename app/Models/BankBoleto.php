<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankBoleto extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'receivable_id',
        'remessa_id',
        'bank_retorno_id',
        'nosso_numero',
        'numero_documento',
        'carteira',
        'codigo_barras',
        'linha_digitavel',
        'data_vencimento',
        'data_pagamento',
        'valor',
        'valor_pago',
        'status',
        'instrucao_remessa',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'data_vencimento' => 'date',
            'data_pagamento' => 'date',
            'valor' => 'decimal:2',
            'valor_pago' => 'decimal:2',
        ];
    }

    public function bankRetorno(): BelongsTo
    {
        return $this->belongsTo(BankRetorno::class, 'bank_retorno_id');
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function remessa(): BelongsTo
    {
        return $this->belongsTo(BankRemessa::class, 'remessa_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }
}
