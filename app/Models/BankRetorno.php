<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankRetorno extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'bank_account_id',
        'nome_arquivo',
        'caminho_arquivo',
        'banco',
        'layout',
        'data_arquivo',
        'data_processamento',
        'quantidade_titulos',
        'quantidade_liquidados',
        'quantidade_baixados',
        'quantidade_entradas',
        'quantidade_alterados',
        'quantidade_erros',
        'quantidade_nao_encontrados',
        'valor_total',
        'log',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'data_arquivo' => 'date',
            'data_processamento' => 'datetime',
            'valor_total' => 'decimal:2',
            'log' => 'array',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function boletos(): HasMany
    {
        return $this->hasMany(BankBoleto::class, 'bank_retorno_id');
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
