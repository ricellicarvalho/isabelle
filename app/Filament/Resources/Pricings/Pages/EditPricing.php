<?php

namespace App\Filament\Resources\Pricings\Pages;

use App\Filament\Resources\Pricings\PricingResource;
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
}
