<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NfseServiceCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tipo_servico',
        'descricao',
        'item_lista_servico',
        'codigo_tributacao_municipio',
        'codigo_tributacao_nacional',
        'codigo_cnae',
        'aliquota',
        'ativo',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'aliquota' => 'decimal:2',
            'ativo'    => 'boolean',
        ];
    }

    public static function paraTipoServico(string $tipoServico): ?self
    {
        return static::where('tipo_servico', $tipoServico)->where('ativo', true)->first();
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
