<?php

namespace App\Filament\Resources\Clients\Actions;

use App\Models\Client;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class GenerateCadastroLink
{
    public static function make(?Client $record = null): Action
    {
        return Action::make('gerarLinkCadastro')
            ->label('Gerar Link de Pré-Cadastro')
            ->icon('heroicon-o-link')
            ->color('info')
            ->visible(fn (): bool => $record !== null)
            ->modalHeading('Link de Pré-Cadastro')
            ->modalDescription('Copie o link abaixo e envie ao cliente para que preencha os dados dos colaboradores.')
            ->modalContent(function () use ($record): HtmlString {
                if (! $record->cadastroTokenValido()) {
                    $record->update([
                        'cadastro_token'           => Str::uuid()->toString(),
                        'cadastro_token_expira_em' => now()->addDays(7),
                    ]);
                    $record->refresh();
                }

                $url      = e(route('precadastro', ['token' => $record->cadastro_token]));
                $expira   = e($record->cadastro_token_expira_em?->format('d/m/Y \à\s H:i') ?? '—');
                $statusHtml = $record->cadastro_preenchido
                    ? '<span style="color:#16a34a;font-weight:600">✓ Preenchido pelo cliente</span>'
                    : '<span style="color:#d97706;font-weight:600">⏳ Aguardando preenchimento</span>';

                return new HtmlString(<<<HTML
                <div style="padding:0 0 0.5rem">
                    <label style="display:block;font-size:0.875rem;font-weight:500;color:#374151;margin-bottom:0.375rem">
                        Link de pré-cadastro
                    </label>
                    <input
                        type="text"
                        value="{$url}"
                        readonly
                        onclick="this.select()"
                        style="width:100%;border:1px solid #d1d5db;border-radius:0.5rem;
                               padding:0.5rem 0.75rem;font-size:0.75rem;font-family:monospace;
                               background:#f3f4f6;color:#111827;outline:none;
                               cursor:text;box-sizing:border-box">
                    <p style="font-size:0.75rem;color:#6b7280;margin-top:0.25rem">
                        Clique no campo e pressione
                        <kbd style="background:#e5e7eb;padding:0.1rem 0.3rem;border-radius:0.25rem;font-size:0.7rem">Ctrl+A</kbd>
                        para selecionar tudo.
                    </p>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;
                                margin-top:1rem;border-top:1px solid #f3f4f6;padding-top:1rem">
                        <div>
                            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;margin:0">Válido até</p>
                            <p style="font-size:0.875rem;color:#111827;margin:0.25rem 0 0">{$expira}</p>
                        </div>
                        <div>
                            <p style="font-size:0.75rem;color:#6b7280;font-weight:500;margin:0">Status do cadastro</p>
                            <p style="font-size:0.875rem;margin:0.25rem 0 0">{$statusHtml}</p>
                        </div>
                    </div>
                </div>
                HTML);
            })
            ->modalSubmitActionLabel('Regenerar Link')
            ->modalCancelActionLabel('Fechar')
            ->action(function () use ($record): void {
                $record->update([
                    'cadastro_token'           => Str::uuid()->toString(),
                    'cadastro_token_expira_em' => now()->addDays(7),
                ]);

                Notification::make()
                    ->title('Link regenerado!')
                    ->body('O link anterior foi invalidado. Clique em "Gerar Link" novamente para copiar o novo.')
                    ->success()
                    ->send();
            });
    }
}
