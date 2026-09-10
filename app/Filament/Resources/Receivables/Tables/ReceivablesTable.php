<?php

namespace App\Filament\Resources\Receivables\Tables;

use App\Filament\Resources\Receivables\Pages\ListReceivables;
use App\Jobs\EmitirNFSeJob;
use App\Models\BankAccount;
use App\Models\Nfse;
use App\Models\NfseConfig;
use App\Models\NfseServiceCode;
use App\Models\Receivable;
use App\Services\BankBoletoService;
use App\Services\BankMovementService;
use App\Services\BoletoBatchService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Throwable;

class ReceivablesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort(fn (Builder $query): Builder => $query
                ->orderByRaw(
                    "CASE
                        WHEN status IN ('pendente', 'vencido') AND data_vencimento < ? THEN 0
                        WHEN status IN ('pendente', 'vencido') AND data_vencimento = ? THEN 1
                        WHEN status IN ('pendente', 'vencido') AND data_vencimento > ? THEN 2
                        ELSE 3
                    END",
                    array_fill(0, 3, today()->toDateString()),
                )
                ->orderBy('data_vencimento')
                ->orderBy('id'))
            ->columns([
                TextColumn::make('client.razao_social')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable()
                    ->limit(30),

                TextColumn::make('contract.numero')
                    ->label('Contrato')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->searchable()
                    ->limit(35),

                TextColumn::make('numero_parcela')
                    ->label('Parcela')
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('saldo_aberto')->label('Saldo aberto')->money('BRL'),
                TextColumn::make('situacao_financeira')->label('Baixa')->badge(),
                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('dias_atraso')
                    ->label('Atraso')
                    ->state(function ($record): ?string {
                        if ($record->status === 'pago' || $record->status === 'cancelado') {
                            return null;
                        }
                        $dias = Carbon::now()->startOfDay()->diffInDays(Carbon::parse($record->data_vencimento)->startOfDay(), false);
                        if ($dias >= 0) {
                            return null;
                        }

                        return abs($dias).' dias';
                    })
                    ->badge()
                    ->color('danger')
                    ->placeholder('—'),

                TextColumn::make('data_pagamento')
                    ->label('Pagamento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('bankAccount.nome')->label('Conta')->placeholder('Não informada')->searchable()->toggleable(),

                TextColumn::make('situacao_cobranca')
                    ->label('Situação')
                    ->state(function (Receivable $record): string {
                        if ($record->status === 'pago') {
                            return 'pago';
                        }

                        if ($record->status === 'cancelado') {
                            return 'cancelado';
                        }

                        $vencimento = $record->data_vencimento->startOfDay();

                        return match (true) {
                            $vencimento->isBefore(today()) => 'em_atraso',
                            $vencimento->isToday() => 'vence_hoje',
                            default => 'a_receber',
                        };
                    })
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'em_atraso' => 'danger',
                        'vence_hoje' => 'warning',
                        'a_receber' => 'info',
                        'pago' => 'success',
                        'cancelado' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'em_atraso' => 'Em atraso',
                        'vence_hoje' => 'Vence hoje',
                        'a_receber' => 'A receber',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                    }),

                // Indicador de boleto(s) gerado(s) — eager loaded via counts()
                TextColumn::make('bank_boletos_count')
                    ->label('Boleto')
                    ->counts('bankBoletos')
                    ->badge()
                    ->alignCenter()
                    ->color(fn (int $state): string => match (true) {
                        $state === 0 => 'gray',
                        $state === 1 => 'success',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (int $state): string => match (true) {
                        $state === 0 => 'Sem boleto',
                        $state === 1 => '1 boleto',
                        default => "{$state} boletos",
                    }),
            ])
            ->filters([
                SelectFilter::make('bank_account_id')->label('Conta prevista / baixas')->options(fn () => BankAccount::orderBy('nome')->get()->pluck('display_name', 'id'))->searchable()
                    ->query(fn (Builder $query, array $data) => \App\Services\FinancialCashService::titleAccount($query, filled($data['value'] ?? null) ? (int) $data['value'] : null)),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
                    ]),

                SelectFilter::make('client_id')
                    ->label('Cliente')
                    ->relationship('client', 'razao_social')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('contract_id')
                    ->label('Contrato')
                    ->relationship('contract', 'numero')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category_id')
                    ->label('Categoria')
                    ->relationship('category', 'descricao')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('forma_pagamento')
                    ->label('Forma de pagamento')
                    ->options([
                        'boleto' => 'Boleto',
                        'pix' => 'PIX',
                        'transferencia' => 'Transferência',
                        'dinheiro' => 'Dinheiro',
                        'cartao' => 'Cartão',
                    ]),

                Filter::make('data_vencimento')
                    ->label('Vencimento')
                    ->schema([
                        DatePicker::make('de')
                            ->label('Vencimento de')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('ate')
                            ->label('Vencimento até')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when(
                            $data['de'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '>=', $date),
                        )
                        ->when(
                            $data['ate'] ?? null,
                            fn (Builder $query, $date): Builder => $query->whereDate('data_vencimento', '<=', $date),
                        )),

                Filter::make('vencidas')
                    ->label('Vencidas (não pagas)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['pendente', 'vencido'])
                        ->whereDate('data_vencimento', '<', now()))
                    ->toggle(),

                Filter::make('vencendo_hoje')
                    ->label('Vencendo hoje')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['pendente', 'vencido'])
                        ->whereDate('data_vencimento', today()))
                    ->toggle(),

                Filter::make('a_receber_futuras')
                    ->label('A receber (futuras)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['pendente', 'vencido'])
                        ->whereDate('data_vencimento', '>', today()))
                    ->toggle(),

                Filter::make('sem_boleto')
                    ->label('Sem boleto gerado')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('bankBoletos'))
                    ->toggle(),

                Filter::make('com_boleto')
                    ->label('Com boleto gerado')
                    ->query(fn (Builder $query): Builder => $query->whereHas('bankBoletos'))
                    ->toggle(),
                Filter::make('sem_baixa_historica')
                    ->label('Pagos sem baixa bancária')
                    ->query(fn (Builder $query): Builder => $query
                        ->where('status', 'pago')
                        ->whereDate('data_pagamento', '>=', BankMovementService::CONTROL_START)
                        ->whereDoesntHave('settlements'))
                    ->toggle(),
            ])
            ->actions([
                ActionGroup::make([
                    // RN11/RN16 - Gerar Boleto a partir da parcela (detecta 2ª via pelo count eager loaded)
                    Action::make('gerarBoleto')
                        ->label(fn (Receivable $record): string => ($record->bank_boletos_count ?? 0) > 0 ? 'Gerar 2ª Via' : 'Gerar Boleto'
                        )
                        ->icon(fn (Receivable $record): string => ($record->bank_boletos_count ?? 0) > 0
                                ? 'heroicon-o-document-duplicate'
                                : 'heroicon-o-document-plus'
                        )
                        ->color(fn (Receivable $record): string => ($record->bank_boletos_count ?? 0) > 0 ? 'warning' : 'info'
                        )
                        ->visible(fn (Receivable $record): bool => in_array($record->status, ['pendente', 'vencido']))
                        ->requiresConfirmation()
                        ->modalHeading(fn (Receivable $record): string => ($record->bank_boletos_count ?? 0) > 0 ? 'Gerar 2ª Via de Boleto' : 'Gerar Boleto'
                        )
                        ->modalDescription(fn (Receivable $record): string => ($record->bank_boletos_count ?? 0) > 0
                                ? 'Já existe pelo menos um boleto para esta parcela. Confirma a geração de uma 2ª via?'
                                : 'Confirma a geração do boleto para esta parcela?'
                        )
                        ->action(function (Receivable $record): void {
                            try {
                                $boleto = BankBoletoService::createFromReceivable($record);

                                Notification::make()
                                    ->title('Boleto gerado com sucesso')
                                    ->body("Nosso Número: {$boleto->nosso_numero}")
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Erro ao gerar boleto')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    // Emissão de NFSe por parcela
                    Action::make('emitirNfse')
                        ->label('Emitir NFSe')
                        ->icon('heroicon-o-document-check')
                        ->color('success')
                        ->visible(fn (Receivable $record): bool => in_array($record->status, ['pendente', 'pago', 'vencido']))
                        ->modalHeading('Emitir NFSe para esta Parcela')
                        ->modalDescription('Cada parcela gera uma NFSe independente (RN conforme contrato).')
                        ->modalWidth('lg')
                        ->form([
                            \Filament\Forms\Components\TextInput::make('valor')
                                ->label('Valor (R$)')
                                ->default(fn (Receivable $record): string => number_format((float) $record->valor, 2, ',', '.'))
                                ->disabled()
                                ->dehydrated(false),
                            \Filament\Forms\Components\DatePicker::make('competencia')
                                ->label('Competência')
                                ->required()
                                ->native(false)
                                ->displayFormat('m/Y')
                                ->default(fn (Receivable $record) => $record->data_vencimento ?? now())
                                ->helperText('Mês de referência da prestação do serviço'),
                            \Filament\Forms\Components\Textarea::make('discriminacao')
                                ->label('Descrição do Serviço')
                                ->required()
                                ->default(fn (Receivable $record): string => $record->descricao
                                    ?? $record->contract?->descricao
                                    ?? ''
                                )
                                ->rows(3)
                                ->maxLength(2000),
                        ])
                        ->action(function (array $data, Receivable $record): void {
                            $config = NfseConfig::ativa();
                            if (! $config) {
                                Notification::make()->title('Configuração NFSe não encontrada')->body('Acesse Configurações > Config NFSe.')->danger()->persistent()->send();

                                return;
                            }
                            $client = $record->client;
                            if (blank($client?->cnpj_cpf)) {
                                Notification::make()->title('CPF/CNPJ do cliente não informado')->danger()->send();

                                return;
                            }
                            if (blank($client?->municipio_ibge)) {
                                Notification::make()->title('Código IBGE não informado')->body("Preencha o Código IBGE no cadastro do cliente '{$client->razao_social}'.")->danger()->persistent()->send();

                                return;
                            }
                            $tipoServico = $record->contract?->tipo_servico ?? 'outro';
                            $serviceCode = NfseServiceCode::paraTipoServico($tipoServico);
                            $aliquota = $serviceCode?->aliquota ?? $config->aliquota_iss_padrao ?? 2.00;
                            $itemLista = $serviceCode?->item_lista_servico ?? $config->item_lista_servico ?? '17.01';
                            $numeroRps = $config->reservarNumeroRps();
                            $nfse = Nfse::create([
                                'contract_id' => $record->contract_id,
                                'contract_version_id' => $record->contract_version_id,
                                'receivable_id' => $record->id,
                                'numero_rps' => $numeroRps,
                                'serie_rps' => $config->serie_rps,
                                'tipo_rps' => 1,
                                'status' => 'pendente',
                                'ambiente' => config('nfse.ambiente'),
                                'valor' => (float) $record->valor,
                                'aliquota' => (float) $aliquota,
                                'iss_retido' => $config->iss_retido,
                                'valor_iss' => round((float) $record->valor * (float) $aliquota / 100, 2),
                                'item_lista_servico' => $itemLista,
                                'discriminacao' => $data['discriminacao'],
                                'competencia' => $data['competencia'],
                                'created_by' => auth()->id(),
                            ]);
                            try {
                                EmitirNFSeJob::dispatch($nfse);
                                Notification::make()->title('NFSe enviada para processamento')->body("RPS #{$numeroRps} gerado.")->success()->send();
                            } catch (\Throwable $e) {
                                $nfse->refresh();
                                Notification::make()
                                    ->title('Falha ao emitir NFSe')
                                    ->body($nfse->ultimo_erro ?: $e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                    \App\Filament\Actions\FinancialSettlementActions::settle(),
                    \App\Filament\Actions\FinancialSettlementActions::reverse(),
                    \App\Filament\Actions\FinancialSettlementActions::history(),
                    EditAction::make(),
                    DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    \App\Filament\Actions\FinancialSettlementActions::historicalBulk('Receivable'),
                    // RN05 - Quitação em Lote
                    BulkAction::make('marcarPago')->visible(fn () => auth()->user()->can('Settle:Receivable'))
                        ->label('Baixar saldo em lote')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->form([
                            Select::make('bank_account_id')->label('Conta bancária')->options(fn () => BankAccount::query()->where('ativo', true)->get()->pluck('display_name', 'id'))->required()->searchable(),
                            DatePicker::make('data_pagamento')->label('Data do recebimento')->default(today())->minDate(BankMovementService::CONTROL_START)->required(),
                        ])
                        ->requiresConfirmation()
                        ->action(function (Collection $records, array $data): void {
                            $count = \Illuminate\Support\Facades\DB::transaction(function () use ($records, $data) {
                                $count = 0;
                                foreach ($records->sortBy('id') as $record) {
                                    if ($record->status === 'pendente' || $record->status === 'vencido') {
                                        \Illuminate\Support\Facades\Gate::authorize('Settle:'.class_basename($record));
                                        app(BankMovementService::class)->settle($record, BankAccount::findOrFail($data['bank_account_id']), $data['data_pagamento'], $record->saldo_aberto);
                                        $count++;
                                    }
                                }

                                return $count;
                            });

                            Notification::make()
                                ->title("{$count} parcela(s) marcada(s) como pagas")
                                ->success()
                                ->send();
                        }),

                    // Gera/reutiliza os boletos e entrega um PDF com uma página por vencimento.
                    BulkAction::make('gerarBoletosLote')
                        ->label('Gerar boletos em PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading(fn (Collection $records): string => app(BoletoBatchService::class)->validationMessage($records)
                            ? 'Não foi possível gerar os boletos'
                            : 'Gerar boletos em PDF')
                        ->modalDescription(fn (Collection $records): string => app(BoletoBatchService::class)->validationMessage($records)
                            ?? 'As parcelas devem pertencer ao mesmo cliente. Boletos existentes serão reutilizados e o arquivo terá uma página por boleto, em ordem de vencimento.')
                        ->modalSubmitAction(fn (Action $action, Collection $records): Action => $action
                            ->disabled(app(BoletoBatchService::class)->validationMessage($records) !== null))
                        ->modalCancelActionLabel(fn (Collection $records): string => app(BoletoBatchService::class)->validationMessage($records)
                            ? 'Fechar'
                            : 'Cancelar')
                        ->action(function (Collection $records) {
                            try {
                                $result = app(BoletoBatchService::class)->generate($records);

                                Notification::make()
                                    ->title(count($result['boletos']).' boleto(s) reunido(s) no PDF')
                                    ->body("{$result['created']} novo(s) e {$result['reused']} reutilizado(s).")
                                    ->success()
                                    ->send();

                                return response()->streamDownload(
                                    fn () => print ($result['pdf']),
                                    $result['filename'],
                                    ['Content-Type' => 'application/pdf'],
                                );
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Não foi possível gerar os boletos')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->persistent()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->contentFooter(function (ListReceivables $livewire) {
                $summary = $livewire->getSelectedReceivablesSummary();

                return view('filament.resources.receivables.selected-total-footer', $summary);
            });
    }
}
