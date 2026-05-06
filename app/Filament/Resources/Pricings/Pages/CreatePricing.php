<?php

namespace App\Filament\Resources\Pricings\Pages;

use App\Filament\Resources\Pricings\PricingResource;
use App\Filament\Resources\Pricings\Schemas\PricingForm;
use Filament\Resources\Pages\CreateRecord;

class CreatePricing extends CreateRecord
{
    protected static string $resource = PricingResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by']   = auth()->id();
        $data['custo_indireto'] = 0;

        foreach (['valor_por_funcionario', 'despesa_encontro', 'despesa_risco',
                  'despesa_relatorio', 'despesas_indiretas', 'despesa_acao_anual', 'deslocamento'] as $field) {
            $data[$field] = PricingForm::parseMoney($data[$field] ?? 0);
        }

        [$data['custo_direto'], $data['preco_venda']] = self::calcularTotais($data);

        return $data;
    }

    public static function calcularTotais(array $data): array
    {
        $margem  = (float) ($data['margem_lucro'] ?? 30) / 100;
        $imposto = (float) ($data['percentual_imposto'] ?? 8) / 100;
        $f       = 1 + $margem;

        $custoApl = (int) ($data['num_funcionarios'] ?? 0)
            * PricingForm::parseMoney($data['valor_por_funcionario'] ?? 0);

        $custoMedio = PricingForm::parseMoney($data['despesa_encontro'] ?? 0)
            + $custoApl
            + PricingForm::parseMoney($data['despesa_risco'] ?? 0)
            + PricingForm::parseMoney($data['despesa_relatorio'] ?? 0)
            + PricingForm::parseMoney($data['despesas_indiretas'] ?? 0)
            + PricingForm::parseMoney($data['despesa_acao_anual'] ?? 0)
            + PricingForm::parseMoney($data['deslocamento'] ?? 0);

        $sImposto = PricingForm::parseMoney($data['despesa_encontro'] ?? 0) * $f
            + $custoApl * $f
            + PricingForm::parseMoney($data['despesa_risco'] ?? 0) * $f
            + PricingForm::parseMoney($data['despesa_relatorio'] ?? 0) * $f
            + PricingForm::parseMoney($data['despesas_indiretas'] ?? 0)
            + PricingForm::parseMoney($data['despesa_acao_anual'] ?? 0) * $f
            + PricingForm::parseMoney($data['deslocamento'] ?? 0);

        $cImposto = $sImposto * (1 + $imposto);

        return [round($custoMedio, 2), round($cImposto, 2)];
    }
}
