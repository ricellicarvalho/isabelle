<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class BankAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::saving(function (BankAccount $account): void {
            if (! $account->uses_billing) {
                $account->is_default_billing = false;
            }
        });
        static::saved(function (BankAccount $account): void {
            if ($account->is_default_billing) {
                static::whereKeyNot($account->id)->update(['is_default_billing' => false]);
            }
        });
    }

    protected $fillable = [
        'nome',
        'banco',
        'descricao',
        'tipo',
        'opening_balance_date',
        'opening_balance',
        'credit_limit',
        'uses_billing',
        'is_default_billing',
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
        'logo_path',
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
            'uses_billing' => 'boolean',
            'is_default_billing' => 'boolean',
            'opening_balance_date' => 'date',
            'opening_balance' => 'decimal:2',
            'credit_limit' => 'decimal:2',
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
        return static::where('ativo', true)->where('uses_billing', true)
            ->orderByDesc('is_default_billing')->first()
            ?? static::where('ativo', true)->first();
    }

    public function movements(): HasMany
    {
        return $this->hasMany(BankMovement::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->nome ?: trim(($this->descricao ?: $this->banco_nome).' · Ag '.$this->agencia.' · '.$this->conta);
    }

    public function balanceAt(mixed $date = null): string
    {
        $until = $date ? \Illuminate\Support\Carbon::parse($date) : now();
        if (is_string($date) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $until->endOfDay();
        }
        if ($until && $this->opening_balance_date && $until->lt($this->opening_balance_date->copy()->endOfDay()->setMicrosecond(0))) {
            return '0.00';
        }
        $query = $this->movements()->where('status', 'confirmed')->where('occurred_at', '>=', \App\Services\BankMovementService::CONTROL_START);
        if ($this->opening_balance_date) {
            $query->where('occurred_at', '>', $this->opening_balance_date->copy()->endOfDay());
        }
        if ($until) {
            $query->where('occurred_at', '<=', $until);
        }
        $credits = (clone $query)->where('direction', 'credit')->sum('amount');
        $debits = (clone $query)->where('direction', 'debit')->sum('amount');

        return bcadd((string) $this->opening_balance, bcsub((string) $credits, (string) $debits, 2), 2);
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
            $next = max(
                (int) $fresh->proximo_sequencial_remessa,
                (int) BankRemessa::query()->max('sequencial_arquivo') + 1,
            );
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
            '756' => 'Sicoob',
            default => 'Banco '.$this->banco,
        };
    }
}
