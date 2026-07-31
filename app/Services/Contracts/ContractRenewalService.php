<?php

namespace App\Services\Contracts;

use App\Models\Contract;
use App\Models\ContractVersion;
use App\Models\ContractVersionChange;
use App\Observers\ContractObserver;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ContractRenewalService
{
    public const VERSIONED_FIELDS = [
        'client_id', 'category_id', 'numero', 'tipo_servico', 'descricao', 'valor_total',
        'forma_pagamento', 'quantidade_parcelas', 'data_inicio', 'data_fim', 'arquivo_pdf',
        'observacoes',
    ];

    public function renew(Contract $contract, array $data, int $userId): ContractVersion
    {
        $data = Validator::make($data, [
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'numero' => ['required', 'string', 'max:255', Rule::unique('contracts', 'numero')->ignore($contract->id)],
            'tipo_servico' => ['required', Rule::in(['nr1', 'palestra', 'consultoria', 'treinamento', 'outro'])],
            'descricao' => ['nullable', 'string'],
            'valor_total' => ['required', 'numeric', 'min:0'],
            'forma_pagamento' => ['required', Rule::in(['boleto', 'pix', 'transferencia', 'dinheiro', 'cartao'])],
            'quantidade_parcelas' => ['required', 'integer', 'min:1', 'max:120'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['required', 'date', 'after_or_equal:data_inicio'],
            'arquivo_pdf' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'change_reason' => ['nullable', 'string', 'max:5000'],
        ])->validate();

        return DB::transaction(function () use ($contract, $data, $userId): ContractVersion {
            /** @var Contract $lockedContract */
            $lockedContract = Contract::query()->lockForUpdate()->findOrFail($contract->getKey());

            if ($lockedContract->status === 'cancelado') {
                throw ValidationException::withMessages([
                    'contract' => 'Contratos cancelados não podem ser renovados.',
                ]);
            }

            /** @var ContractVersion|null $previous */
            $previous = $lockedContract->versions()->lockForUpdate()->first();
            if (! $previous) {
                throw ValidationException::withMessages([
                    'contract' => 'O contrato não possui uma versão inicial. Execute a verificação da migração.',
                ]);
            }

            foreach (['client_id', 'category_id', 'numero', 'tipo_servico'] as $immutableField) {
                if ((string) $data[$immutableField] !== (string) $previous->getAttribute($immutableField)) {
                    throw ValidationException::withMessages([
                        $immutableField => 'Este campo não pode ser alterado durante uma renovação.',
                    ]);
                }
            }

            $values = Arr::only($data, self::VERSIONED_FIELDS);
            $values['valor_total'] = (float) $values['valor_total'];
            $values['quantidade_parcelas'] = (int) $values['quantidade_parcelas'];

            $previous->update(['status' => 'superseded']);

            $version = $lockedContract->versions()->create($values + [
                'previous_version_id' => $previous->id,
                'version_number' => $previous->version_number + 1,
                'change_type' => 'renewal',
                'status' => 'active',
                'change_reason' => $data['change_reason'] ?? null,
                'activated_at' => now(),
                'created_by' => $userId,
                'activated_by' => $userId,
            ]);

            foreach (self::VERSIONED_FIELDS as $field) {
                $old = $this->comparableValue($previous->getAttribute($field));
                $new = $this->comparableValue($version->getAttribute($field));

                if ($old !== $new) {
                    ContractVersionChange::create([
                        'contract_version_id' => $version->id,
                        'field' => $field,
                        'old_value' => $old,
                        'new_value' => $new,
                        'created_by' => $userId,
                    ]);
                }
            }

            Contract::withoutEvents(function () use ($lockedContract, $values, $version): void {
                $lockedContract->update($values + [
                    'current_version_id' => $version->id,
                    'status' => 'ativo',
                ]);
            });

            app(ContractObserver::class)->generateReceivables($lockedContract->fresh(), $version);

            return $version->fresh(['changes']);
        }, 3);
    }

    private function comparableValue(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return (string) $value;
    }
}
