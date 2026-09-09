<?php

namespace App\Models\Concerns;

use App\Services\BankMovementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

trait HasFinancialSettlements
{
    public function save(array $options = [])
    {
        return DB::transaction(function () use ($options) {
            if ($this->exists) {
                $fresh = $this->newQueryWithoutScopes()->lockForUpdate()->findOrFail($this->id);
                if ($this->isDirty('status') && $this->status === 'pago' && $fresh->status !== 'pago' && (! $this->data_pagamento || $this->data_pagamento->gte(BankMovementService::CONTROL_START))) {
                    throw ValidationException::withMessages(['status' => 'Use Dar baixa ou Receber para registrar o pagamento.']);
                }
                if ($this->settlements()->lockForUpdate()->get()->isNotEmpty() && $this->isDirty(['valor', 'valor_pago', 'data_pagamento', 'forma_pagamento', 'bank_account_id', 'status', 'category_id', 'client_id', 'supplier_id', 'descricao', 'fornecedor'])) {
                    throw ValidationException::withMessages(['status' => 'Este título possui baixas. Use as ações de baixa ou estorno para alterar os dados financeiros.']);
                }
            }

            return parent::save($options);
        });
    }

    public function delete()
    {
        return DB::transaction(function () {
            $this->newQueryWithoutScopes()->lockForUpdate()->findOrFail($this->id);

            return parent::delete();
        });
    }

    public static function bootHasFinancialSettlements(): void
    {
        static::deleting(function ($title) {
            if ($title->settlements()->exists()) {
                throw ValidationException::withMessages(['status' => 'Não é permitido excluir títulos com histórico de baixas.']);
            }
        });
    }

    public function getSaldoAbertoAttribute(): string
    {
        return app(BankMovementService::class)->openAmount($this);
    }

    public function getSituacaoFinanceiraAttribute(): string
    {
        if ($this->status === 'cancelado') {
            return 'Cancelado';
        }
        if (bccomp($this->saldo_aberto, '0', 2) === 0) {
            return 'Pago';
        }
        if ($this->settlements()->where('status', 'confirmed')->exists()) {
            return 'Parcial';
        }

        return $this->data_vencimento->lt(today()) ? 'Vencido' : 'Em aberto';
    }
}
