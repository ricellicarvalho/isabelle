<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nfse extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'contract_id',
        'receivable_id',
        'numero_rps',
        'serie_rps',
        'tipo_rps',
        'numero',
        'chave_dfe',
        'codigo_verificacao',
        'data_emissao',
        'competencia',
        'status',
        'ambiente',
        'valor',
        'aliquota',
        'iss_retido',
        'valor_iss',
        'item_lista_servico',
        'discriminacao',
        'xml',
        'pdf',
        'xml_cancelamento',
        'motivo_cancelamento',
        'codigo_cancelamento',
        'tentativas',
        'ultimo_erro',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'valor'       => 'decimal:2',
            'aliquota'    => 'decimal:2',
            'valor_iss'   => 'decimal:2',
            'data_emissao' => 'datetime',
            'competencia' => 'date',
            'tentativas'  => 'integer',
        ];
    }

    public function pdfBytes(): ?string
    {
        return $this->pdf ? hex2bin($this->pdf) : null;
    }

    public function xmlBytes(): ?string
    {
        return $this->xml ? hex2bin($this->xml) : null;
    }

    public function isEmitida(): bool
    {
        return $this->status === 'emitida';
    }

    public function isCancelada(): bool
    {
        return $this->status === 'cancelada';
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function receivable(): BelongsTo
    {
        return $this->belongsTo(Receivable::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deletedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(NfseLog::class);
    }
}
