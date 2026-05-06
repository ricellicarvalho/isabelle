<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class NfseConfig extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'cnpj',
        'inscricao_municipal',
        'razao_social',
        'nome_fantasia',
        'email',
        'telefone',
        'endereco',
        'numero',
        'complemento',
        'bairro',
        'municipio_ibge',
        'nome_municipio',
        'uf',
        'codigo_uf',
        'cep',
        'simples_nacional',
        'regime_especial_tributacao',
        'padrao_nacional',
        'serie_rps',
        'proximo_numero_rps',
        'aliquota_iss_padrao',
        'item_lista_servico',
        'codigo_tributacao_municipio',
        'codigo_cnae',
        'exigibilidade_iss',
        'iss_retido',
        'ativo',
        'responsavel_retencao',
        'usuario_prefeitura',
        'senha_prefeitura',
        'frase_secreta',
        'chave_acesso',
        'chave_autorizacao',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'padrao_nacional'             => 'boolean',
            'ativo'                       => 'boolean',
            'aliquota_iss_padrao'         => 'decimal:2',
            'regime_especial_tributacao'  => 'integer',
            'proximo_numero_rps'          => 'integer',
        ];
    }

    public static function ativa(): ?self
    {
        return static::where('ativo', true)->first();
    }

    /**
     * Reserva atomicamente o próximo número de RPS e incrementa o contador.
     */
    public function reservarNumeroRps(): int
    {
        return DB::transaction(function () {
            $config = static::lockForUpdate()->findOrFail($this->id);
            $numero = $config->proximo_numero_rps;
            $config->increment('proximo_numero_rps');

            return $numero;
        });
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
