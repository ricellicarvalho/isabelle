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
    /**
     * @param 'documentacao'|'financeiro' $tipo
     */
    public static function make(?Client $record = null, string $tipo = 'documentacao'): Action
    {
        $config = PortalAccessSlots::get($tipo);
        $emailField = $config['email_field'];
        $fk = $config['fk'];

        return Action::make("generatePortalAccess_{$tipo}")
            ->label($config['item_gerar'])
            ->icon('heroicon-o-key')
            ->color('success')
            ->visible(fn (): bool => $record !== null && ! $record->{$fk})
            ->disabled(fn (): bool => $record === null || ! filled($record->{$emailField}))
            ->tooltip(fn (): ?string => ($record !== null && ! filled($record->{$emailField}))
                ? "Preencha o campo \"{$config['campo_label']}\" antes de gerar este acesso."
                : null)
            ->requiresConfirmation()
            ->modalHeading($config['label_gerar'])
            ->modalDescription("Será criado um login e senha para o {$config['descricao_pessoa']}. O e-mail informado em \"{$config['campo_label']}\" será usado como login.")
            ->modalSubmitActionLabel('Gerar Acesso')
            ->action(function () use ($record, $emailField, $fk, $config): void {
                if (! $record) {
                    return;
                }

                $email = $record->{$emailField};

                if (! filled($email)) {
                    Notification::make()
                        ->title('E-mail obrigatório')
                        ->body("Preencha o campo \"{$config['campo_label']}\" antes de gerar o acesso.")
                        ->danger()
                        ->send();

                    return;
                }

                if (User::where('email', $email)->exists()) {
                    Notification::make()
                        ->title('E-mail já cadastrado')
                        ->body("Já existe um usuário com o e-mail {$email}. Altere o e-mail e tente novamente.")
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                $password = Str::password(length: 8, symbols: false);
                $nomeBase = filled($record->nome_fantasia) ? $record->nome_fantasia : $record->razao_social;
                $name = "{$nomeBase} ({$config['sufixo_nome']})";

                $user = User::create([
                    'name'     => $name,
                    'email'    => $email,
                    'password' => Hash::make($password),
                    'is_admin' => false,
                ]);

                $user->assignRole('cliente');

                $record->update([$fk => $user->id]);

                Log::info('Portal access generated', [
                    'tipo'       => $config['tipo'],
                    'client_id'  => $record->id,
                    'user_id'    => $user->id,
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Acesso ao portal criado!')
                    ->body(
                        "Login: {$email}\n" .
                        "Senha: {$password}\n\n" .
                        "Copie a senha agora — ela não será exibida novamente."
                    )
                    ->success()
                    ->persistent()
                    ->send();
            });
    }
}
