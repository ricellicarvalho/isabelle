<?php

namespace App\Filament\Resources\Contracts\Tables;

use App\Models\Contract;
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
