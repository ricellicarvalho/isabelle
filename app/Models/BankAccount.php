<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'banco',
        'descricao',
        'agencia',
        'agencia_dv',
        'conta',
        'conta_dv',
        'carteira',
        'convenio',
        'cedente_nome',
        'cedente_documento',
        'cedente_endereco',
        'cedente_cidade_uf',
        'layout_remessa',
        'proximo_nosso_numero',
        'proximo_sequencial_remessa',
        'ativo',
        'created_by',
        'deleted_by',
    ];

    protected function casts(): array
    {
        return [
            'ativo' => 'boolean',
            'proximo_nosso_numero' => 'integer',
            'proximo_sequencial_remessa' => 'integer',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function active(): ?self
    {
        return static::where('ativo', true)->first();
    }

    /**
     * RN12 - Reserva o próximo nosso número de forma atômica.
     */
    public function reserveNossoNumero(): int
    {
        return DB::transaction(function () {
            $fresh = static::query()->lockForUpdate()->find($this->id);
            $next = $fresh->proximo_nosso_numero;
            $fresh->update(['proximo_nosso_numero' => $next + 1]);

            return $next;
        });
    }

    public function reserveSequencialRemessa(): int
    {
        return DB::transaction(function () {
            $fresh = static::query()->lockForUpdate()->find($this->id);
            $next = $fresh->proximo_sequencial_remessa;
            $fresh->update(['proximo_sequencial_remessa' => $next + 1]);

            return $next;
        });
    }

    public function getBancoNomeAttribute(): string
    {
        return match ($this->banco) {
            '001' => 'Banco do Brasil',
            '033' => 'Santander',
            '104' => 'Caixa Econômica Federal',
            '237' => 'Bradesco',
            '341' => 'Itaú',
            default => 'Banco ' . $this->banco,
        };
    }
}
