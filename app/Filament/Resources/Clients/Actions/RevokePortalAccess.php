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
    /**
     * @param 'documentacao'|'financeiro' $tipo
     */
    public static function make(?Client $record = null, string $tipo = 'documentacao'): Action
    {
        $config = PortalAccessSlots::get($tipo);
        $fk = $config['fk'];

        return Action::make("revokePortalAccess_{$tipo}")
            ->label($config['item_revogar'])
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (): bool => $record !== null && (bool) $record->{$fk})
            ->requiresConfirmation()
            ->modalHeading($config['label_revogar'])
            ->modalDescription('O acesso será removido imediatamente. Você pode gerar um novo depois se necessário.')
            ->modalSubmitActionLabel('Revogar Acesso')
            ->action(function () use ($record, $fk, $config): void {
                if (! $record || ! $record->{$fk}) {
                    return;
                }

                $userId = $record->{$fk};
                $user = User::find($userId);

                $record->update([$fk => null]);

                if ($user) {
                    $user->delete();
                }

                Log::info('Portal access revoked', [
                    'tipo'       => $config['tipo'],
                    'client_id'  => $record->id,
                    'user_id'    => $userId,
                    'revoked_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Acesso revogado')
                    ->body('O acesso a este portal foi removido.')
                    ->success()
                    ->send();
            });
    }
}
