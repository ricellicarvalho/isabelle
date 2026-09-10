<?php

namespace App\Filament\Resources\BankMovements\Pages;

use App\Filament\Resources\BankMovements\BankMovementResource;
use App\Filament\Resources\BankMovements\Schemas\BankMovementForm;
use App\Models\BankAccount;
use App\Services\BankMovementService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Utilities\Get;

class ListBankMovements extends ListRecords
{
    protected static string $resource = BankMovementResource::class;

    protected function getHeaderActions(): array
    {
        return [Action::make('report')->label('Movimentação de Conta')->visible(fn () => \App\Filament\Pages\BankAccountReport::canAccess())->url(fn () => \App\Filament\Pages\BankAccountReport::getUrl()), CreateAction::make()->label('Lançamento avulso'), Action::make('transfer')->label('Transferir entre contas')->schema([
            Select::make('from')->label('Conta de origem')->options(fn () => BankAccount::where('ativo', true)->get()->pluck('display_name', 'id'))->required()->searchable()->live()
                ->disableOptionWhen(fn (string $value, Get $get): bool => (string) $get('to') === $value),
            Select::make('to')->label('Conta de destino')->options(fn () => BankAccount::where('ativo', true)->get()->pluck('display_name', 'id'))->required()->different('from')->searchable()->live()
                ->disableOptionWhen(fn (string $value, Get $get): bool => (string) $get('from') === $value),
            DatePicker::make('date')->label('Data')->default(today())->required()->native(false)->displayFormat('d/m/Y'), BankMovementForm::moneyInput('amount'), TextInput::make('description')->label('Histórico'),
        ])->action(function (array $data): void {
            app(BankMovementService::class)->transfer(BankAccount::findOrFail($data['from']), BankAccount::findOrFail($data['to']), $data['date'], $data['amount'], $data['description'] ?? null);
            Notification::make()->success()->title('Transferência registrada')->send();
        })];
    }
}
