<?php

namespace App\Filament\Resources\BankBoletos\Tables;

use App\Models\BankBoleto;
use App\Services\CnabRemessaService;
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
use Illuminate\Support\Facades\URL;
use Throwable;

class BankBoletosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_vencimento', 'asc')
            ->columns([
                TextColumn::make('nosso_numero')
                    ->label('Nosso Número')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('receivable.client.razao_social')
                    ->label('Cliente')
                    ->searchable()
                    ->limit(30)
                    ->placeholder('—'),

                TextColumn::make('receivable.descricao')
                    ->label('Parcela')
                    ->limit(30)
                    ->placeholder('—'),

                TextColumn::make('carteira')
                    ->label('Carteira')
                    ->placeholder('—'),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('linha_digitavel')
                    ->label('Linha Digitável')
                    ->placeholder('—')
                    ->copyable()
                    ->copyMessage('Linha digitável copiada')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('remessa.sequencial_arquivo')
                    ->label('Remessa')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente' => 'warning',
                        'emitido' => 'info',
                        'pago' => 'success',
                        'cancelado' => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente' => 'Pendente',
                        'emitido' => 'Emitido',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pendente' => 'Pendente',
                        'emitido' => 'Emitido',
                        'pago' => 'Pago',
                        'cancelado' => 'Cancelado',
                    ]),

                SelectFilter::make('carteira')
                    ->label('Carteira')
                    ->options(fn () => \App\Models\BankBoleto::query()
                        ->whereNotNull('carteira')
                        ->distinct()
                        ->pluck('carteira', 'carteira')
                        ->toArray()),
            ])
            ->actions([
                // URL assinada com validade de 30 min — abre em nova aba sem passar pelo Livewire
                Action::make('baixarPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->url(fn (BankBoleto $record): string =>
                        URL::signedRoute('boleto.pdf', ['boleto' => $record->id], now()->addMinutes(30))
                    )
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    // RN13 - Gerar Arquivo de Remessa
                    BulkAction::make('gerarRemessa')
                        ->label('Gerar Arquivo de Remessa')
                        ->icon('heroicon-o-archive-box-arrow-down')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalDescription('Gera um arquivo CNAB com os boletos selecionados (apenas pendentes ou cancelados). Boletos cancelados entrarão com instrução de baixa.')
                        ->action(function (Collection $records): void {
                            try {
                                $remessa = CnabRemessaService::generate($records);

                                Notification::make()
                                    ->title("Remessa #{$remessa->sequencial_arquivo} gerada")
                                    ->body("{$remessa->quantidade_titulos} título(s) — R$ " . number_format((float) $remessa->valor_total, 2, ',', '.'))
                                    ->success()
                                    ->send();
                            } catch (Throwable $e) {
                                Notification::make()
                                    ->title('Erro ao gerar remessa')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
