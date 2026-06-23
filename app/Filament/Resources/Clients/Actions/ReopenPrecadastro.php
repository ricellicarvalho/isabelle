<?php

namespace App\Filament\Resources\Clients\Actions;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class ReopenPrecadastro
{
    public static function make(?Client $record = null): Action
    {
        return Action::make('reopenPrecadastro')
            ->label('Reabrir Pré-Cadastro')
            ->icon('heroicon-o-lock-open')
            ->color('warning')
            ->visible(fn (): bool => $record !== null && $record->cadastro_preenchido && filled($record->cadastro_token))
            ->requiresConfirmation()
            ->modalHeading('Reabrir Pré-Cadastro')
            ->modalDescription('O cliente poderá acessar o mesmo link novamente para corrigir os dados já enviados (o formulário virá pré-preenchido com as informações atuais). O link continuará válido por mais 7 dias.')
            ->modalSubmitActionLabel('Reabrir')
            ->action(function () use ($record): void {
                if (! $record) {
                    return;
                }

                $record->update([
                    'cadastro_preenchido'      => false,
                    'cadastro_token_expira_em' => now()->addDays(7),
                ]);

                Notification::make()
                    ->title('Pré-cadastro reaberto!')
                    ->body('O cliente já pode acessar o mesmo link para corrigir as informações.')
                    ->success()
                    ->send();
            });
    }
}
