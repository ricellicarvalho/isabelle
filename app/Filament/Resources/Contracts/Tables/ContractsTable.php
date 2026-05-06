<?php

namespace App\Filament\Resources\Contracts\Tables;

use App\Models\Contract;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class ContractsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº Contrato')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('client.razao_social')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(35),

                TextColumn::make('tipo_servico')
                    ->label('Serviço')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'nr1' => 'primary',
                        'palestra' => 'info',
                        'consultoria' => 'warning',
                        'treinamento' => 'success',
                        'outro' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'nr1' => 'NR-1',
                        'palestra' => 'Palestra',
                        'consultoria' => 'Consultoria',
                        'treinamento' => 'Treinamento',
                        'outro' => 'Outro',
                    }),

                TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('quantidade_parcelas')
                    ->label('Parcelas')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('data_inicio')
                    ->label('Início')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('data_fim')
                    ->label('Fim')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'rascunho' => 'gray',
                        'ativo' => 'success',
                        'finalizado' => 'info',
                        'cancelado' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'rascunho' => 'Rascunho',
                        'ativo' => 'Ativo',
                        'finalizado' => 'Finalizado',
                        'cancelado' => 'Cancelado',
                    }),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'rascunho' => 'Rascunho',
                        'ativo' => 'Ativo',
                        'finalizado' => 'Finalizado',
                        'cancelado' => 'Cancelado',
                    ]),

                SelectFilter::make('tipo_servico')
                    ->label('Tipo de Serviço')
                    ->options([
                        'nr1' => 'NR-1',
                        'palestra' => 'Palestra',
                        'consultoria' => 'Consultoria',
                        'treinamento' => 'Treinamento',
                        'outro' => 'Outro',
                    ]),

                SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'razao_social')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Action::make('emitirNfse')
                    ->label('Emitir NFSe')
                    ->icon('heroicon-o-document-check')
                    ->color('success')
                    ->visible(fn (Contract $record): bool => in_array($record->status, ['ativo', 'finalizado']))
                    ->modalHeading('Emitir Nota Fiscal de Serviço Eletrônica')
                    ->modalDescription('Preencha ou confirme os dados abaixo antes de emitir a NFSe.')
                    ->modalWidth('lg')
                    ->form([
                        \Filament\Forms\Components\TextInput::make('valor')
                            ->label('Valor (R$)')
                            ->default(fn (Contract $record): string => number_format((float) $record->valor_total, 2, ',', '.'))
                            ->disabled()
                            ->dehydrated(false),
                        \Filament\Forms\Components\DatePicker::make('competencia')
                            ->label('Competência')
                            ->required()
                            ->native(false)
                            ->displayFormat('m/Y')
                            ->default(now())
                            ->helperText('Mês de referência da prestação do serviço'),
                        \Filament\Forms\Components\Textarea::make('discriminacao')
                            ->label('Descrição do Serviço')
                            ->required()
                            ->default(fn (Contract $record): string => $record->descricao ?? '')
                            ->rows(3)
                            ->maxLength(2000),
                    ])
                    ->action(function (array $data, Contract $record): void {
                        $config = \App\Models\NfseConfig::ativa();
                        if (! $config) {
                            Notification::make()->title('Configuração NFSe não encontrada')->body('Acesse Configurações > Config NFSe e cadastre os dados do prestador.')->danger()->persistent()->send();
                            return;
                        }
                        $client = $record->client;
                        if (blank($client?->cnpj_cpf)) {
                            Notification::make()->title('CPF/CNPJ do cliente não informado')->danger()->send();
                            return;
                        }
                        if (blank($client?->municipio_ibge)) {
                            Notification::make()->title('Código IBGE do município do cliente não informado')->body("Acesse o cadastro do cliente '{$client->razao_social}' e preencha o campo Código IBGE.")->danger()->persistent()->send();
                            return;
                        }
                        $serviceCode = \App\Models\NfseServiceCode::paraTipoServico($record->tipo_servico);
                        $aliquota    = $serviceCode?->aliquota ?? $config->aliquota_iss_padrao ?? 2.00;
                        $itemLista   = $serviceCode?->item_lista_servico ?? $config->item_lista_servico ?? '17.01';
                        $numeroRps   = $config->reservarNumeroRps();
                        $nfse = \App\Models\Nfse::create([
                            'contract_id'        => $record->id,
                            'receivable_id'      => null,
                            'numero_rps'         => $numeroRps,
                            'serie_rps'          => $config->serie_rps,
                            'tipo_rps'           => 1,
                            'status'             => 'pendente',
                            'ambiente'           => config('nfse.ambiente'),
                            'valor'              => (float) $record->valor_total,
                            'aliquota'           => (float) $aliquota,
                            'iss_retido'         => $config->iss_retido,
                            'valor_iss'          => round((float) $record->valor_total * (float) $aliquota / 100, 2),
                            'item_lista_servico' => $itemLista,
                            'discriminacao'      => $data['discriminacao'],
                            'competencia'        => $data['competencia'],
                            'created_by'         => auth()->id(),
                        ]);
                        try {
                            \App\Jobs\EmitirNFSeJob::dispatch($nfse);
                            \Filament\Notifications\Notification::make()->title('NFSe enviada para processamento')->body("RPS #{$numeroRps} — aguarde a confirmação.")->success()->send();
                        } catch (\Throwable $e) {
                            $nfse->refresh();
                            \Filament\Notifications\Notification::make()
                                ->title('Falha ao emitir NFSe')
                                ->body($nfse->ultimo_erro ?: $e->getMessage())
                                ->danger()
                                ->persistent()
                                ->send();
                        }
                    }),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // RN04 - Faturamento em Lote
                    BulkAction::make('faturar')
                        ->label('Faturar (gerar parcelas)')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalDescription('Para cada contrato selecionado, garante que as parcelas (Contas a Receber) estejam geradas. Contratos cancelados são ignorados.')
                        ->action(function (Collection $records): void {
                            $contratosFaturados = 0;
                            $parcelasCriadas = 0;

                            foreach ($records as $contract) {
                                /** @var Contract $contract */
                                if ($contract->status === 'cancelado') {
                                    continue;
                                }
                                if ($contract->receivables()->count() > 0) {
                                    continue;
                                }

                                // Ativa o contrato (dispara o Observer que gera parcelas)
                                if ($contract->status !== 'ativo') {
                                    $contract->update(['status' => 'ativo']);
                                } else {
                                    // Status já era ativo mas não há parcelas — força geração
                                    app(\App\Observers\ContractObserver::class)->generateReceivables($contract);
                                }

                                $contratosFaturados++;
                                $parcelasCriadas += $contract->receivables()->count();
                            }

                            Notification::make()
                                ->title("{$contratosFaturados} contrato(s) faturado(s)")
                                ->body("{$parcelasCriadas} parcela(s) gerada(s)")
                                ->success()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
