<?php

namespace App\Filament\Resources\Nfses\Tables;

use App\Filament\Resources\Nfses\Actions\CancelarNFSeAction;
use App\Jobs\EmitirNFSeJob;
use App\Models\Nfse;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\URL;

class NfsesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº NFSe')
                    ->placeholder('Pendente')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('numero_rps')
                    ->label('RPS')
                    ->sortable(),

                TextColumn::make('contract.numero')
                    ->label('Contrato')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('receivable.client.razao_social')
                    ->label('Cliente')
                    ->getStateUsing(fn (Nfse $record): string =>
                        $record->receivable?->client?->razao_social
                        ?? $record->contract?->client?->razao_social
                        ?? '—'
                    )
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereHas('receivable.client', fn ($q) => $q->where('razao_social', 'like', "%{$search}%"))
                        ->orWhereHas('contract.client', fn ($q) => $q->where('razao_social', 'like', "%{$search}%"))
                    )
                    ->limit(30),

                TextColumn::make('discriminacao')
                    ->label('Serviço')
                    ->limit(35)
                    ->toggleable(),

                TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('aliquota')
                    ->label('ISS %')
                    ->formatStateUsing(fn ($state): string => number_format((float) $state, 2, ',', '.') . '%')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('competencia')
                    ->label('Competência')
                    ->date('m/Y')
                    ->sortable(),

                TextColumn::make('ambiente')
                    ->label('Ambiente')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state === '1' ? 'Produção' : 'Homologação')
                    ->color(fn ($state): string => $state === '1' ? 'success' : 'warning')
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendente'    => 'gray',
                        'processando' => 'info',
                        'emitida'     => 'success',
                        'cancelada'   => 'danger',
                        'erro'        => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pendente'    => 'Pendente',
                        'processando' => 'Processando',
                        'emitida'     => 'Emitida',
                        'cancelada'   => 'Cancelada',
                        'erro'        => 'Erro',
                    }),

                TextColumn::make('ultimo_erro')
                    ->label('Último Erro')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable()
                    ->color('danger')
                    ->visible(fn (): bool => true),

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
                        'pendente'    => 'Pendente',
                        'processando' => 'Processando',
                        'emitida'     => 'Emitida',
                        'cancelada'   => 'Cancelada',
                        'erro'        => 'Erro',
                    ]),

                SelectFilter::make('ambiente')
                    ->label('Ambiente')
                    ->options([
                        '1' => 'Produção',
                        '2' => 'Homologação',
                    ]),
            ])
            ->actions([
                Action::make('downloadPdf')
                    ->label('PDF')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('danger')
                    ->visible(fn (Nfse $record): bool => filled($record->pdf))
                    ->url(fn (Nfse $record): string =>
                        URL::signedRoute('nfse.pdf', ['nfse' => $record->id], now()->addMinutes(30))
                    )
                    ->openUrlInNewTab(),

                Action::make('downloadXml')
                    ->label('XML')
                    ->icon('heroicon-o-code-bracket')
                    ->color('gray')
                    ->visible(fn (Nfse $record): bool => filled($record->xml))
                    ->url(fn (Nfse $record): string =>
                        URL::signedRoute('nfse.xml', ['nfse' => $record->id], now()->addMinutes(30))
                    )
                    ->openUrlInNewTab(),

                CancelarNFSeAction::make(),

                DeleteAction::make()
                    ->visible(fn (Nfse $record): bool => in_array($record->status, ['erro', 'cancelada'])),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('reprocessar')
                        ->label('Reprocessar (Erros)')
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalDescription('Reenvia para processamento as NFSes com status "Erro".')
                        ->action(function (Collection $records): void {
                            $count = 0;

                            foreach ($records as $nfse) {
                                if ($nfse->status !== 'erro') {
                                    continue;
                                }

                                $nfse->update(['status' => 'pendente', 'ultimo_erro' => null]);
                                EmitirNFSeJob::dispatch($nfse);
                                $count++;
                            }

                            Notification::make()
                                ->title("{$count} NFSe(s) reprocessada(s)")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
