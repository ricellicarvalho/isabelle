<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Filament\Resources\Contracts\Schemas\ContractForm;
use App\Models\Category;
use App\Models\Client;
use App\Models\Contract;
use App\Services\Contracts\ContractRenewalService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class RenewContractAction
{
    public static function make(): Action
    {
        return Action::make('renewContract')
            ->label('Renovar contrato')
            ->icon('heroicon-o-arrow-path')
            ->color('primary')
            ->visible(fn (Contract $record): bool => in_array($record->status, ['ativo', 'finalizado'], true))
            ->modalHeading(fn (Contract $record): string => "Renovar contrato {$record->numero}")
            ->modalDescription('Os dados atuais já estão preenchidos. Altere somente o necessário; a versão anterior será preservada integralmente.')
            ->modalWidth('4xl')
            ->fillForm(fn (Contract $record): array => [
                'client_id' => $record->client_id,
                'category_id' => $record->category_id,
                'numero' => $record->numero,
                'tipo_servico' => $record->tipo_servico,
                'descricao' => $record->descricao,
                'valor_total' => number_format((float) $record->valor_total, 2, ',', '.'),
                'forma_pagamento' => $record->forma_pagamento,
                'quantidade_parcelas' => $record->quantidade_parcelas,
                'data_inicio' => $record->data_fim?->copy()->addDay(),
                'data_fim' => $record->data_fim?->copy()->addYear(),
                'arquivo_pdf' => null,
                'observacoes' => $record->observacoes,
            ])
            ->form([
                Select::make('client_id')->label('Cliente')
                    ->options(fn (): array => Client::query()->orderBy('razao_social')->pluck('razao_social', 'id')->all())
                    ->searchable()->required()->native(false)->disabled()->dehydrated(),
                Select::make('category_id')->label('Categoria')
                    ->options(fn (): array => Category::query()->orderBy('descricao')->pluck('descricao', 'id')->all())
                    ->searchable()->required()->native(false)->disabled()->dehydrated(),
                TextInput::make('numero')->label('Número do Contrato')->required()->maxLength(255)->disabled()->dehydrated(),
                Select::make('tipo_servico')->label('Tipo de Serviço')->options([
                    'nr1' => 'NR-1', 'palestra' => 'Palestra', 'consultoria' => 'Consultoria',
                    'treinamento' => 'Treinamento', 'outro' => 'Outro',
                ])->required()->native(false)->disabled()->dehydrated(),
                Textarea::make('descricao')->label('Descrição')->rows(2)->columnSpanFull(),
                TextInput::make('valor_total')->label('Valor Total')->prefix('R$')->required(),
                TextInput::make('quantidade_parcelas')->label('Quantidade de Parcelas')->numeric()->minValue(1)->maxValue(120)->required(),
                Select::make('forma_pagamento')->label('Forma de Pagamento')->options([
                    'boleto' => 'Boleto', 'pix' => 'PIX', 'transferencia' => 'Transferência',
                    'dinheiro' => 'Dinheiro', 'cartao' => 'Cartão',
                ])->required()->native(false),
                DatePicker::make('data_inicio')->label('Novo início')->required()->native(false)->displayFormat('d/m/Y'),
                DatePicker::make('data_fim')->label('Novo fim')->required()->native(false)->displayFormat('d/m/Y')->afterOrEqual('data_inicio'),
                FileUpload::make('arquivo_pdf')->label('Novo contrato/aditivo em PDF')
                    ->acceptedFileTypes(['application/pdf'])->directory('contratos')->preserveFilenames()->maxSize(10240),
                Textarea::make('observacoes')->label('Observações')->rows(2)->columnSpanFull(),
                Textarea::make('change_reason')->label('Motivo da renovação')->rows(3)
                    ->helperText('Opcional. Quando informado, este texto ficará registrado no histórico.')
                    ->columnSpanFull(),
            ])
            ->requiresConfirmation()
            ->modalSubmitActionLabel('Confirmar renovação')
            ->action(function (array $data, Contract $record): void {
                $data['valor_total'] = ContractForm::parseMoney($data['valor_total']) ?? 0;
                $version = app(ContractRenewalService::class)->renew($record, $data, (int) auth()->id());

                Notification::make()
                    ->title("Contrato renovado — Versão {$version->version_number}")
                    ->body('A versão anterior foi preservada no histórico e as novas parcelas foram geradas.')
                    ->success()
                    ->send();
            });
    }
}
