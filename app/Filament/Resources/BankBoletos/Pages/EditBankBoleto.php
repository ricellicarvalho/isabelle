<?php

namespace App\Filament\Resources\BankBoletos\Pages;

use App\Filament\Resources\BankBoletos\BankBoletoResource;
use App\Services\BankBoletoService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EditBankBoleto extends EditRecord
{
    protected static string $resource = BankBoletoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('baixarPdf')
                ->label('Baixar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function (): StreamedResponse {
                    $record = $this->getRecord();
                    $pdf = BankBoletoService::renderPdf($record);

                    return response()->streamDownload(
                        fn () => print($pdf),
                        "boleto-{$record->nosso_numero}.pdf",
                        ['Content-Type' => 'application/pdf']
                    );
                }),
            DeleteAction::make(),
        ];
    }
}
