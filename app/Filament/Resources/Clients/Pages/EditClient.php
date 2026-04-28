<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\Actions\GenerateCadastroLink;
use App\Filament\Resources\Clients\Actions\GeneratePortalAccess;
use App\Filament\Resources\Clients\Actions\ResetPortalPassword;
use App\Filament\Resources\Clients\Actions\RevokePortalAccess;
use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            GenerateCadastroLink::make($this->record),
            GeneratePortalAccess::make($this->record),
            ResetPortalPassword::make($this->record),
            RevokePortalAccess::make($this->record),
            DeleteAction::make(),
        ];
    }
}
