<?php

namespace App\Filament\Resources\Clients\Actions;

use App\Models\Client;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class RevokePortalAccess
{
    public static function make(?Client $record = null): Action
    {
        return Action::make('revokePortalAccess')
            ->label('Revogar Acesso ao Portal')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (): bool => $record !== null && (bool) $record->portal_user_id)
            ->requiresConfirmation()
            ->modalHeading('Revogar Acesso ao Portal')
            ->modalDescription('O cliente perderá o acesso ao portal imediatamente. Você pode gerar um novo acesso depois se necessário.')
            ->modalSubmitActionLabel('Revogar Acesso')
            ->action(function () use ($record): void {
                if (! $record || ! $record->portal_user_id) {
                    return;
                }

                $userId = $record->portal_user_id;
                $user = User::find($userId);

                $record->update(['portal_user_id' => null]);

                if ($user) {
                    $user->delete();
                }

                Log::info('Portal access revoked', [
                    'client_id'  => $record->id,
                    'user_id'    => $userId,
                    'revoked_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Acesso revogado')
                    ->body('O cliente não tem mais acesso ao portal.')
                    ->success()
                    ->send();
            });
    }
}
