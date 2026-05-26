<?php

namespace App\Filament\Resources\Contracts\Pages;

use App\Filament\Resources\Contracts\ContractResource;
use App\Filament\Resources\Contracts\Schemas\ContractForm;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditContract extends EditRecord
{
    protected static string $resource = ContractResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cancelarContrato')
                ->label('Cancelar Contrato')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (): bool => in_array($this->record->status, ['rascunho', 'ativo']))
                ->requiresConfirmation()
                ->modalHeading(fn (): string => "Cancelar Contrato {$this->record->numero}")
                ->modalDescription(function (): string {
                    $pendentes = $this->record->receivables()->where('status', 'pendente')->count();

                    $msg = "Você está prestes a cancelar o contrato {$this->record->numero}.";

                    if ($pendentes > 0) {
                        $msg .= " {$pendentes} parcela(s) pendente(s) vinculada(s) a este contrato também serão canceladas automaticamente.";
                    }

                    $msg .= ' Esta operação não pode ser desfeita.';

                    return $msg;
                })
                ->modalSubmitActionLabel('Sim, cancelar contrato')
                ->modalCancelActionLabel('Voltar')
                ->action(function (): void {
                    $this->record->update(['status' => 'cancelado']);

                    Notification::make()
                        ->title('Contrato cancelado')
                        ->body("O contrato {$this->record->numero} foi cancelado com sucesso.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status']);
                }),

            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['valor_total'] = ContractForm::parseMoney($data['valor_total'] ?? null) ?? 0;

        return $data;
    }
}
