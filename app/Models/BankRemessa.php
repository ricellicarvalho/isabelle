<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankRemessa extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sequencial_arquivo',
        'data_geracao',
        'caminho_arquivo',
        'quantidade_titulos',
        'valor_total',
        'layout',
        'status',
        'arquivo_baixado_at',
        'arquivo_baixado_by',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'data_geracao' => 'datetime',
            'arquivo_baixado_at' => 'datetime',
            'valor_total' => 'decimal:2',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function arquivoBaixadoBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'arquivo_baixado_by');
    }

    public function boletos(): HasMany
    {
        return $this->hasMany(BankBoleto::class, 'remessa_id');
    }

    public function registrarDownloadArquivo(?int $userId = null): void
    {
        $updates = [
            'arquivo_baixado_at' => now(),
            'arquivo_baixado_by' => $userId,
        ];

        if ($this->status === 'gerado') {
            $updates['status'] = 'enviado';
        }

        $this->update($updates);
    }
}
