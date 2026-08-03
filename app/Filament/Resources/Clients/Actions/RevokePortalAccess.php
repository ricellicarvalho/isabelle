<?php

namespace App\Filament\Resources\Clients\Actions;

use App\Models\Client;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class RevokePortalAccess
{
    /**
     * @param  'documentacao'|'financeiro'  $tipo
     */
    public static function make(?Client $record = null, string $tipo = 'documentacao'): Action
    {
        $config = PortalAccessSlots::get($tipo);
        $fk = $config['fk'];
        $passwordField = $config['password_field'];

        return Action::make("revokePortalAccess_{$tipo}")
            ->label($config['item_revogar'])
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (): bool => $record !== null && (bool) $record->{$fk})
            ->requiresConfirmation()
            ->modalHeading($config['label_revogar'])
            ->modalDescription(function () use ($record, $fk): string {
                if (! $record || ! $record->{$fk}) {
                    return 'O acesso será removido imediatamente. Você pode gerar um novo depois se necessário.';
                }

                $vinculos = Client::query()
                    ->where('portal_user_id', $record->{$fk})
                    ->orWhere('portal_financeiro_user_id', $record->{$fk})
                    ->count();

                return $vinculos > 1
                    ? 'Este usuário acessa mais de uma empresa. Apenas o vínculo deste cliente será removido.'
                    : 'O acesso será removido imediatamente. Você pode gerar um novo depois se necessário.';
            })
            ->modalSubmitActionLabel('Revogar Acesso')
            ->action(function (Component $livewire) use ($record, $fk, $passwordField, $config): void {
                if (! $record || ! $record->{$fk}) {
                    return;
                }

                $userId = $record->{$fk};
                $user = User::find($userId);

                $record->update([
                    $fk => null,
                    $passwordField => null,
                ]);
                $record->refresh();
                self::refreshClientForm($livewire, [$fk, $passwordField]);

                $userStillLinked = Client::query()
                    ->where('portal_user_id', $userId)
                    ->orWhere('portal_financeiro_user_id', $userId)
                    ->exists();

                if ($user && ! $userStillLinked) {
                    $user->delete();
                }

                Log::info('Portal access revoked', [
                    'tipo' => $config['tipo'],
                    'client_id' => $record->id,
                    'user_id' => $userId,
                    'revoked_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Acesso revogado')
                    ->body('O acesso a este portal foi removido.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @param  array<string>  $statePaths
     */
    private static function refreshClientForm(Component $livewire, array $statePaths): void
    {
        if (method_exists($livewire, 'refreshFormData')) {
            $livewire->refreshFormData($statePaths);
        }
    }
}
