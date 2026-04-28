<?php

namespace App\Filament\Resources\Clients\Actions;

use App\Models\Client;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResetPortalPassword
{
    public static function make(?Client $record = null): Action
    {
        return Action::make('resetPortalPassword')
            ->label('Resetar Senha do Portal')
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (): bool => $record !== null && (bool) $record->portal_user_id)
            ->requiresConfirmation()
            ->modalHeading('Resetar Senha do Portal')
            ->modalDescription('Uma nova senha aleatória será gerada. A senha anterior será invalidada imediatamente.')
            ->modalSubmitActionLabel('Resetar Senha')
            ->action(function () use ($record): void {
                if (! $record || ! $record->portal_user_id) {
                    return;
                }

                $user = User::find($record->portal_user_id);

                if (! $user) {
                    Notification::make()
                        ->title('Usuário não encontrado')
                        ->body('O usuário vinculado ao portal não existe mais. Revogue o acesso e gere um novo.')
                        ->danger()
                        ->send();

                    return;
                }

                $password = Str::password(length: 8, symbols: false);

                $user->update(['password' => Hash::make($password)]);

                Log::info('Portal password reset', [
                    'client_id' => $record->id,
                    'user_id'   => $user->id,
                    'reset_by'  => Auth::id(),
                ]);

                Notification::make()
                    ->title('Senha resetada!')
                    ->body(
                        "Login: {$user->email}\n" .
                        "Nova Senha: {$password}\n\n" .
                        "Copie a senha agora — ela não será exibida novamente."
                    )
                    ->warning()
                    ->persistent()
                    ->send();
            });
    }
}
