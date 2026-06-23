<?php

namespace App\Filament\Resources\Clients\Pages;

use App\Filament\Resources\Clients\Actions\GenerateCadastroLink;
use App\Filament\Resources\Clients\Actions\GeneratePortalAccess;
use App\Filament\Resources\Clients\Actions\ReopenPrecadastro;
use App\Filament\Resources\Clients\Actions\ResetPortalPassword;
use App\Filament\Resources\Clients\Actions\RevokePortalAccess;
use App\Filament\Resources\Clients\ClientResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Illuminate\Support\HtmlString;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    private const CHEVRON_DOWN = '<svg class="w-3.5 h-3.5 inline-block ml-1 -mt-0.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.27a.75.75 0 01.02-1.06z" clip-rule="evenodd" /></svg>';

    protected function getHeaderActions(): array
    {
        return [
            GenerateCadastroLink::make($this->record),
            ReopenPrecadastro::make($this->record),

            ActionGroup::make([
                GeneratePortalAccess::make($this->record, 'documentacao'),
                ResetPortalPassword::make($this->record, 'documentacao'),
                RevokePortalAccess::make($this->record, 'documentacao'),
            ])
                ->label(new HtmlString('Acesso à Documentação' . self::CHEVRON_DOWN))
                ->icon('heroicon-o-key')
                ->color('info')
                ->button()
                ->dropdownWidth(Width::ExtraSmall),

            ActionGroup::make([
                GeneratePortalAccess::make($this->record, 'financeiro'),
                ResetPortalPassword::make($this->record, 'financeiro'),
                RevokePortalAccess::make($this->record, 'financeiro'),
            ])
                ->label(new HtmlString('Acesso Financeiro' . self::CHEVRON_DOWN))
                ->icon('heroicon-o-banknotes')
                ->color('warning')
                ->button()
                ->dropdownWidth(Width::ExtraSmall),

            DeleteAction::make(),
        ];
    }
}
