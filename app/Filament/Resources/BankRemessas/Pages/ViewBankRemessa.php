<?php

namespace App\Filament\Resources\BankRemessas\Pages;

use App\Filament\Resources\BankRemessas\BankRemessaResource;
use App\Models\BankRemessa;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ViewBankRemessa extends ViewRecord
{
    protected static string $resource = BankRemessaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('baixarArquivo')
                ->label('Baixar Arquivo CNAB')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->visible(function (): bool {
                    /** @var BankRemessa $record */
                    $record = $this->getRecord();

                    return $record->caminho_arquivo
                        && Storage::disk('local')->exists($record->caminho_arquivo);
                })
                ->action(function (): StreamedResponse {
                    /** @var BankRemessa $record */
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
