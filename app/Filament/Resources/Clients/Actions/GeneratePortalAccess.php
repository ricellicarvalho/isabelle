<?php

namespace App\Filament\Resources\Clients\Actions;

use App\Models\Client;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class GeneratePortalAccess
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
            ->modalDescription(function () use ($record, $emailField, $config): string {
                if ($record !== null
                    && filled($record->{$emailField})
                    && User::where('email', $record->{$emailField})->exists()) {
                    return "Já existe um usuário com o e-mail informado em \"{$config['campo_label']}\". Confirme que é o mesmo responsável para vincular este cliente ao acesso existente.";
                }

                return "Será criado um login e senha para o {$config['descricao_pessoa']}. O e-mail informado em \"{$config['campo_label']}\" será usado como login.";
            })
            ->form([
                Checkbox::make('confirmar_usuario_existente')
                    ->label('Vincular este cliente ao usuário já existente deste e-mail')
                    ->helperText('Use esta opção somente quando o mesmo responsável administra mais de uma empresa. A senha será a mesma do acesso já existente.')
                    ->visible(fn (): bool => $record !== null
                        && filled($record->{$emailField})
                        && User::where('email', $record->{$emailField})->exists())
                    ->accepted()
                    ->validationMessages([
                        'accepted' => 'Confirme que este e-mail pertence ao mesmo responsável antes de vincular o acesso.',
                    ]),
            ])
            ->modalSubmitActionLabel('Gerar Acesso')
            ->action(function (array $data, Component $livewire) use ($record, $emailField, $fk, $passwordField, $config): void {
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

                $existingUser = User::where('email', $email)->first();

                if ($existingUser && ! ($data['confirmar_usuario_existente'] ?? false)) {
                    Notification::make()
                        ->title('E-mail já cadastrado')
                        ->body("Já existe um usuário com o e-mail {$email}. Confirme no modal que este e-mail pertence ao mesmo responsável para reutilizar o acesso.")
                        ->danger()
                        ->persistent()
                        ->send();

                    return;
                }

                if ($existingUser) {
                    $password = Client::query()
                        ->where(function ($query) use ($existingUser): void {
                            $query
                                ->where('portal_user_id', $existingUser->id)
                                ->orWhere('portal_financeiro_user_id', $existingUser->id);
                        })
                        ->whereKeyNot($record->id)
                        ->get()
                        ->map(fn (Client $client): ?string => $client->portal_last_generated_password ?: $client->portal_financeiro_last_generated_password)
                        ->filter()
                        ->first();

                    if (! $existingUser->hasRole('cliente')) {
                        $existingUser->assignRole('cliente');
                    }

                    $record->update([
                        $fk => $existingUser->id,
                        $passwordField => $password,
                    ]);
                    $record->refresh();
                    self::refreshClientForm($livewire, [$fk, $passwordField]);

                    Log::info('Portal access linked to existing user', [
                        'tipo' => $config['tipo'],
                        'client_id' => $record->id,
                        'user_id' => $existingUser->id,
                        'linked_by' => Auth::id(),
                    ]);

                    Notification::make()
                        ->title('Acesso vinculado ao usuário existente')
                        ->body(
                            "Login: {$email}\n".
                            ($password
                                ? "Senha: {$password}\n\n"
                                : "Senha: mesma já utilizada por este usuário.\n\n").
                            'Ao entrar no portal, este usuário poderá alternar entre as empresas vinculadas.'
                        )
                        ->success()
                        ->persistent()
                        ->send();

                    return;
                }

                $password = Str::password(length: 8, symbols: false);
                $nomeBase = filled($record->nome_fantasia) ? $record->nome_fantasia : $record->razao_social;
                $name = "{$nomeBase} ({$config['sufixo_nome']})";

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'is_admin' => false,
                ]);

                $user->assignRole('cliente');

                $record->update([
                    $fk => $user->id,
                    $passwordField => $password,
                ]);
                $record->refresh();
                self::refreshClientForm($livewire, [$fk, $passwordField]);

                Log::info('Portal access generated', [
                    'tipo' => $config['tipo'],
                    'client_id' => $record->id,
                    'user_id' => $user->id,
                    'created_by' => Auth::id(),
                ]);

                Notification::make()
                    ->title('Acesso ao portal criado!')
                    ->body(
                        "Login: {$email}\n".
                        "Senha: {$password}\n\n".
                        'A senha também ficará disponível no cadastro do cliente, no campo com botão de mostrar.'
                    )
                    ->success()
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
