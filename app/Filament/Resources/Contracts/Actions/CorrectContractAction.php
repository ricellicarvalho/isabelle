<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Filament\Resources\Contracts\ContractResource;
use App\Models\Contract;
use App\Services\Contracts\ContractCorrectionService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;

class CorrectContractAction
{
    public static function make(): Action
    {
        return Action::make('correctContract')
            ->label('Corrigir contrato')
            ->icon('heroicon-o-pencil-square')
            ->color('warning')
            ->visible(fn (Contract $record): bool => in_array($record->status, ['ativo', 'finalizado'], true))
            ->authorize(fn (Contract $record): bool => auth()->user()?->can('update', $record) ?? false)
            ->modalHeading(fn (Contract $record): string => "Corrigir contrato {$record->numero}")
            ->modalDescription(fn (Contract $record): string => self::description($record))
            ->modalWidth('2xl')
            ->fillForm(fn (Contract $record): array => [
                'data_inicio' => $record->data_inicio,
                'data_fim' => $record->data_fim,
                'observacoes' => $record->observacoes,
            ])
            ->form([
                DatePicker::make('data_inicio')
                    ->label('Data de Início corrigida')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                DatePicker::make('data_fim')
                    ->label('Data de Fim corrigida')
                    ->required()
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->afterOrEqual('data_inicio'),
                Textarea::make('observacoes')
                    ->label('Observações')
                    ->rows(3)
                    ->columnSpanFull(),
                Textarea::make('change_reason')
                    ->label('Motivo da correção')
                    ->helperText('Obrigatório. Ficará registrado no histórico do contrato.')
                    ->required()
                    ->rows(3)
                    ->maxLength(5000)
                    ->columnSpanFull(),
            ])
            ->requiresConfirmation()
            ->modalSubmitActionLabel('Confirmar correção')
            ->successRedirectUrl(fn (Contract $record): string => ContractResource::getUrl('edit', ['record' => $record]))
            ->action(function (array $data, Contract $record): void {
                $version = app(ContractCorrectionService::class)->correct($record, $data, (int) auth()->id());

                Notification::make()
                    ->title("Contrato corrigido — Versão {$version->version_number}")
                    ->body('A versão anterior foi preservada e nenhuma nova parcela foi criada.')
                    ->success()
                    ->send();
            });
    }

    protected static function description(Contract $record): string
    {
        $pending = $record->receivables()->whereIn('status', ['pendente', 'vencido'])->count();

        return "A versão anterior será preservada. {$pending} parcela(s) não paga(s) terão o vencimento recalculado; nenhuma nova parcela será criada.";
    }
}
