<?php

namespace App\Filament\Resources\Nfses\Actions;

use App\Jobs\CancelarNFSeJob;
use App\Models\Nfse;
use App\Models\NfseConfig;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class CancelarNFSeAction
{
    public static function make(): Action
    {
        return Action::make('cancelarNfse')
            ->label('Cancelar NFSe')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->visible(fn (Nfse $record): bool => $record->isEmitida())
            ->requiresConfirmation()
            ->modalHeading('Cancelar Nota Fiscal')
            ->modalDescription('Esta operação cancela a NFSe junto à prefeitura e não pode ser desfeita.')
            ->modalWidth('lg')
            ->form([
                Select::make('codigo_cancelamento')
                    ->label('Código de Cancelamento')
                    ->required()
                    ->native(false)
                    ->options([
                        '1' => '1 — Erro na emissão',
                        '2' => '2 — Serviço não prestado',
                        '3' => '3 — Duplicidade da nota',
                        '4' => '4 — Erro de assinatura',
                        '5' => '5 — Abatimento incorreto do ISS',
                        '6' => '6 — Imposto zerado — ISS a ser retido',
                    ]),

                Textarea::make('motivo_cancelamento')
                    ->label('Motivo do Cancelamento')
                    ->required()
                    ->rows(3)
                    ->minLength(10)
                    ->maxLength(500)
                    ->placeholder('Descreva o motivo do cancelamento...'),
            ])
            ->action(function (array $data, Nfse $record): void {
                $config = NfseConfig::ativa();

                if (! $config) {
                    Notification::make()
                        ->title('Configuração NFSe não encontrada')
                        ->body('Cadastre os dados do prestador antes de cancelar.')
                        ->danger()
                        ->send();

                    return;
                }

                if (blank($record->xml)) {
                    Notification::make()
                        ->title('XML da NFSe não disponível')
                        ->body('Não é possível cancelar uma NFSe sem o XML original.')
                        ->danger()
                        ->send();

                    return;
                }

                CancelarNFSeJob::dispatch($record, $data['motivo_cancelamento'], $data['codigo_cancelamento']);

                Notification::make()
                    ->title('Cancelamento enviado para processamento')
                    ->body("NFSe #{$record->numero} está sendo cancelada.")
                    ->warning()
                    ->send();
            });
    }
}
