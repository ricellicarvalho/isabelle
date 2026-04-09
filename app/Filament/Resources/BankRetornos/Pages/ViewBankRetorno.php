<?php

namespace App\Filament\Resources\BankRetornos\Pages;

use App\Filament\Resources\BankRetornos\BankRetornoResource;
use App\Models\BankRetorno;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewBankRetorno extends ViewRecord
{
    protected static string $resource = BankRetornoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('baixarArquivo')
                ->label('Baixar Arquivo')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(function (): bool {
                    /** @var BankRetorno $record */
                    $record = $this->getRecord();

                    return $record->caminho_arquivo
                        && Storage::disk('local')->exists($record->caminho_arquivo);
                })
                ->action(function (): StreamedResponse {
                    /** @var BankRetorno $record */
                    $record = $this->getRecord();

                    return response()->streamDownload(
                        fn () => print(Storage::disk('local')->get($record->caminho_arquivo)),
                        basename($record->caminho_arquivo),
                        ['Content-Type' => 'text/plain']
                    );
                }),
        ];
    }
}
