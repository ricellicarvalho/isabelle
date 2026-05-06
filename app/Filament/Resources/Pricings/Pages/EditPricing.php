<?php

namespace App\Filament\Resources\Pricings\Pages;

use App\Filament\Resources\Pricings\Pages\CreatePricing;
use App\Filament\Resources\Pricings\PricingResource;
use App\Filament\Resources\Pricings\Schemas\PricingForm;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditPricing extends EditRecord
{
    protected static string $resource = PricingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportarPdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    $record = $this->getRecord()->load('category');
                    $pdf = Pdf::loadView('pdf.pricing', ['pricing' => $record]);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        'precificacao-' . \Illuminate\Support\Str::slug($record->nome) . '.pdf',
                        ['Content-Type' => 'application/pdf']
                    );
                }),
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['custo_indireto'] = 0;

        foreach (['valor_por_funcionario', 'despesa_encontro', 'despesa_risco',
                  'despesa_relatorio', 'despesas_indiretas', 'despesa_acao_anual', 'deslocamento'] as $field) {
            $data[$field] = PricingForm::parseMoney($data[$field] ?? 0);
        }

        [$data['custo_direto'], $data['preco_venda']] = CreatePricing::calcularTotais($data);

        return $data;
    }
}
