<?php

namespace App\Filament\Portal\Resources\ContractResource\Pages;

use App\Filament\Portal\Resources\ContractResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ViewContract extends ViewRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('downloadPdf')
                ->label('Baixar Contrato PDF')
                ->icon(Heroicon::DocumentArrowDown)
                ->color('primary')
                ->visible(fn (): bool => filled($this->record->arquivo_pdf))
                ->action(function () {
                    $path     = $this->record->arquivo_pdf;
                    $filename = $this->record->numero . '_' . now()->format('Y-m-d_H-i') . '.pdf';

                    return response()->download(
                        Storage::disk('local')->path($path),
                        $filename,
                        ['Content-Type' => 'application/pdf']
                    );
                }),
        ];
    }
}
