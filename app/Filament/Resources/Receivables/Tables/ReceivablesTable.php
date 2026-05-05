<?php

namespace App\Filament\Resources\Receivables\Tables;

use App\Models\Receivable;
use App\Services\BankBoletoService;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
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
            ->defaultSort('data_vencimento', 'asc')
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

                        return abs($dias) . ' dias';
                    })
                    ->badge()
                    ->color('danger')
                    ->placeholder('—'),

                TextColumn::make('data_pagamento')
                    ->label('Pagamento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'pago' => 'success',
                        'cancelado' => 'gray',
                        'vencido' => 'danger',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => 'Pendente',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                        'vencido' => 'Vencido',
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
                        default      => 'warning',
                    })
                    ->formatStateUsing(fn (int $state): string => match (true) {
                        $state === 0 => 'Sem boleto',
                        $state === 1 => '1 boleto',
                        default      => "{$state} boletos",
                    }),
            ])
            ->filters([
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

                Filter::make('vencidas')
                    ->label('Vencidas (não pagas)')
                    ->query(fn (Builder $query): Builder => $query
                        ->whereIn('status', ['pendente', 'vencido'])
                        ->whereDate('data_vencimento', '<', now()))
                    ->toggle(),

                Filter::make('sem_boleto')
                    ->label('Sem boleto gerado')
                    ->query(fn (Builder $query): Builder => $query->whereDoesntHave('bankBoletos'))
                    ->toggle(),

                Filter::make('com_boleto')
                    ->label('Com boleto gerado')
                    ->query(fn (Builder $query): Builder => $query->whereHas('bankBoletos'))
                    ->toggle(),
            ])
            ->actions([
                // RN11/RN16 - Gerar Boleto a partir da parcela (detecta 2ª via pelo count eager loaded)
                Action::make('gerarBoleto')
                    ->label(fn (Receivable $record): string =>
                        ($record->bank_boletos_count ?? 0) > 0 ? 'Gerar 2ª Via' : 'Gerar Boleto'
                    )
                    ->icon(fn (Receivable $record): string =>
                        ($record->bank_boletos_count ?? 0) > 0
                            ? 'heroicon-o-document-duplicate'
                            : 'heroicon-o-document-plus'
                    )
                    ->color(fn (Receivable $record): string =>
                        ($record->bank_boletos_count ?? 0) > 0 ? 'warning' : 'info'
                    )
                    ->visible(fn (Receivable $record): bool => in_array($record->status, ['pendente', 'vencido']))
                    ->requiresConfirmation()
                    ->modalHeading(fn (Receivable $record): string =>
                        ($record->bank_boletos_count ?? 0) > 0 ? 'Gerar 2ª Via de Boleto' : 'Gerar Boleto'
                    )
                    ->modalDescription(fn (Receivable $record): string =>
                        ($record->bank_boletos_count ?? 0) > 0
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
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // RN05 - Quitação em Lote
                    BulkAction::make('marcarPago')
                        ->label('Marcar como Pago')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (Collection $records): void {
                            $count = 0;
                            foreach ($records as $record) {
                                if ($record->status === 'pendente' || $record->status === 'vencido') {
                                    $record->update([
                                        'status' => 'pago',
                                        'data_pagamento' => now(),
                                        'valor_pago' => $record->valor,
                                    ]);
                                    $count++;
                                }
                            }

                            Notification::make()
                                ->title("{$count} parcela(s) marcada(s) como pagas")
                                ->success()
                                ->send();
                        }),

                    // Geração de boletos em lote — reporta erros individuais sem interromper o lote
                    BulkAction::make('gerarBoletosLote')
                        ->label('Gerar Boletos')
                        ->icon('heroicon-o-document-plus')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalDescription('Gera boletos para todas as parcelas selecionadas com forma de pagamento "boleto" que ainda não possuem boleto vinculado.')
                        ->action(function (Collection $records): void {
                            $count = 0;
                            $errors = 0;

                            foreach ($records as $record) {
                                if ($record->forma_pagamento !== 'boleto') {
                                    continue;
                                }
                                if (! in_array($record->status, ['pendente', 'vencido'])) {
                                    continue;
                                }
                                if ($record->bankBoletos()->exists()) {
                                    continue;
                                }

                                try {
                                    BankBoletoService::createFromReceivable($record);
                                    $count++;
                                } catch (Throwable) {
                                    $errors++;
                                }
                            }

                            if ($errors > 0) {
                                Notification::make()
                                    ->title("{$count} boleto(s) gerado(s) — {$errors} erro(s)")
                                    ->warning()
                                    ->send();
                            } else {
                                Notification::make()
                                    ->title("{$count} boleto(s) gerado(s)")
                                    ->success()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
