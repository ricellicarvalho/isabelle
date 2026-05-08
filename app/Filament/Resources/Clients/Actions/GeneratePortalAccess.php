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

class GeneratePortalAccess
{
    public static function make(?Client $record = null): Action
    {
        return Action::make('generatePortalAccess')
            ->label('Gerar Acesso ao Portal')
            ->icon('heroicon-o-key')
            ->color('success')
            ->visible(fn (): bool => $record !== null && ! $record->portal_user_id)
            ->requiresConfirmation()
            ->modalHeading('Gerar Acesso ao Portal')
            ->modalDescription('Será criado um login e senha para este cliente acessar o portal. O e-mail do cliente será usado como login.')
            ->modalSubmitActionLabel('Gerar Acesso')
            ->action(function () use ($record): void {
                if (! $record) {
                    return;
                }

                if (! filled($record->email)) {
                    Notification::make()
                        ->title('E-mail obrigatório')
                        ->body('Preencha o e-mail do cliente antes de gerar o acesso ao portal.')
                        ->danger()
                        ->send();

                    return;
                }

                if (User::where('email', $record->email)->exists()) {
                    Notification::make()
                        ->title('E-mail já cadastrado')
                        ->body("Já existe um usuário com o e-mail {$record->email}. Altere o e-mail do cliente e tente novamente.")
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $password = Str::password(length: 8, symbols: false);
                $name = filled($record->nome_fantasia) ? $record->nome_fantasia : $record->razao_social;

                $user = User::create([
                    'name'     => $name,
                    'email'    => $record->email,
                    'password' => Hash::make($password),
                    'is_admin' => false,
                ]);

                $user->assignRole('cliente');

                $record->update(['portal_user_id' => $user->id]);

                Log::info('Portal access generated', [
                    'client_id'  => $record->id,
                    'user_id'    => $user->id,
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Acesso ao portal criado!')
                    ->body(
                        "Login: {$record->email}\n" .
                        "Senha: {$password}\n\n" .
                        "Copie a senha agora — ela não será exibida novamente."
                    )
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
