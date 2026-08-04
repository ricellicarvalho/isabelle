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
use Livewire\Component;

class ResetPortalPassword
{
    /**
     * @param  'documentacao'|'financeiro'  $tipo
     */
    public static function make(?Client $record = null, string $tipo = 'documentacao'): Action
    {
        $config = PortalAccessSlots::get($tipo);
        $emailField = $config['email_field'];
        $fk = $config['fk'];
        $passwordField = $config['password_field'];

        return Action::make("resetPortalPassword_{$tipo}")
            ->label($config['item_resetar'])
            ->icon('heroicon-o-arrow-path')
            ->color('warning')
            ->visible(fn (): bool => $record !== null && (bool) $record->{$fk})
            ->requiresConfirmation()
            ->modalHeading($config['label_resetar'])
            ->modalDescription(function () use ($record, $fk): string {
                if (! $record || ! $record->{$fk}) {
                    return 'Uma nova senha aleatória será gerada. A senha anterior será invalidada imediatamente.';
                }

                $vinculos = Client::query()
                    ->where('portal_user_id', $record->{$fk})
                    ->orWhere('portal_financeiro_user_id', $record->{$fk})
                    ->count();

                return $vinculos > 1
                    ? 'Este usuário acessa mais de uma empresa. A nova senha valerá para todos os acessos vinculados a este e-mail.'
                    : 'Uma nova senha aleatória será gerada. A senha anterior será invalidada imediatamente.';
            })
            ->modalSubmitActionLabel('Resetar Senha')
            ->action(function (Component $livewire) use ($record, $emailField, $fk, $config): void {
                if (! $record || ! $record->{$fk}) {
                    return;
                }

                $user = User::find($record->{$fk});

                if (! $user) {
                    Notification::make()
                        ->title('Usuário não encontrado')
                        ->body('O usuário vinculado a este acesso não existe mais. Revogue o acesso e gere um novo.')
                        ->danger()
                        ->send();

                    return;
                }

                $email = Str::lower(trim((string) $record->{$emailField}));

                if (! filled($email)) {
                    Notification::make()
                        ->title('E-mail obrigatório')
                        ->body("Preencha o campo \"{$config['campo_label']}\" antes de resetar a senha.")
                        ->danger()
                        ->send();

                    return;
                }

                $emailAlreadyUsed = User::query()
                    ->where('email', $email)
                    ->whereKeyNot($user->id)
                    ->exists();

                if ($emailAlreadyUsed) {
                    Notification::make()
                        ->title('E-mail já usado em outro acesso')
                        ->body("O e-mail {$email} já pertence a outro usuário. Revogue o acesso atual ou ajuste o cadastro antes de resetar a senha.")
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $vinculos = Client::query()
                    ->where('portal_user_id', $user->id)
                    ->orWhere('portal_financeiro_user_id', $user->id)
                    ->count();

                if (! hash_equals(Str::lower($user->email), $email) && $vinculos > 1) {
                    Notification::make()
                        ->title('Usuário compartilhado com e-mail divergente')
                        ->body('Este usuário acessa mais de uma empresa. Revogue e gere um novo acesso para este cliente antes de alterar o e-mail de login.')
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $password = Str::password(length: 8, symbols: false);

                $user->update([
                    'email' => $email,
                    'password' => Hash::make($password),
                ]);
                Client::query()
                    ->where('portal_user_id', $user->id)
                    ->get()
                    ->each(fn (Client $client) => $client->update(['portal_last_generated_password' => $password]));

                Client::query()
                    ->where('portal_financeiro_user_id', $user->id)
                    ->get()
                    ->each(fn (Client $client) => $client->update(['portal_financeiro_last_generated_password' => $password]));
                $record->refresh();
                self::refreshClientForm($livewire, [
                    'portal_last_generated_password',
                    'portal_financeiro_last_generated_password',
                ]);

                Log::info('Portal password reset', [
                    'tipo' => $config['tipo'],
                    'client_id' => $record->id,
                    'user_id' => $user->id,
                    'login_email' => $user->email,
                    'reset_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Senha resetada!')
                    ->body(
                        "Login: {$user->email}\n".
                        "Nova Senha: {$password}\n\n".
                        'A senha também ficará disponível no cadastro do cliente, no campo com botão de mostrar.'
                    )
                    ->warning()
                    ->persistent()
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
