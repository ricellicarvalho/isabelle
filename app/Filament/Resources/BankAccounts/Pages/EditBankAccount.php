<?php

namespace App\Filament\Resources\BankAccounts\Pages;

use App\Filament\Resources\BankAccounts\BankAccountResource;
use App\Services\OfxImportService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditBankAccount extends EditRecord
{
    protected static string $resource = BankAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('report')->label('Movimentação de Conta')->visible(fn () => \App\Filament\Pages\BankAccountReport::canAccess())->url(fn () => \App\Filament\Pages\BankAccountReport::getUrl(['account' => $this->record->id])), Action::make('importOfx')->label('Importar OFX')->schema([
            FileUpload::make('ofx')->label('Arquivo OFX')->disk('local')->directory('ofx-imports')->acceptedFileTypes(['application/x-ofx', 'application/octet-stream', 'text/plain'])->maxSize(10240)->required(),
        ])->action(function (array $data): void {
            $path = $data['ofx'];
            $import = app(OfxImportService::class)->import($this->record, Storage::disk('local')->get($path), basename($path), auth()->id());
            Storage::disk('local')->delete($path);
            Notification::make()->success()->title($import->entries()->count().' movimentações importadas')->send();
        }), DeleteAction::make()];
    }
}
