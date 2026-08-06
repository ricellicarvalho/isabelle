<?php

namespace App\Filament\Resources\BankRemessas\Tables;

use App\Models\BankRemessa;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankRemessasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_geracao', 'desc')
            ->columns([
                TextColumn::make('sequencial_arquivo')
                    ->label('Sequencial')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('layout')
                    ->label('Layout')
                    ->placeholder('—'),

                TextColumn::make('data_geracao')
                    ->label('Gerada em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('quantidade_titulos')
                    ->label('Qtd. Títulos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('valor_total')
                    ->label('Valor Total')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'gerado' => 'warning',
                        'enviado' => 'info',
                        'processado' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'gerado' => 'Gerado',
                        'enviado' => 'Arquivo baixado',
                        'processado' => 'Processado',
                        default => $state,
                    })
                    ->placeholder('—'),

                TextColumn::make('arquivo_baixado_at')
                    ->label('Baixado em')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('arquivoBaixadoBy.name')
                    ->label('Baixado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('baixarArquivo')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (BankRemessa $record): bool => $record->caminho_arquivo && Storage::disk('local')->exists($record->caminho_arquivo))
                    ->action(function (BankRemessa $record): StreamedResponse {
                        $record->registrarDownloadArquivo(auth()->id());

                        return response()->streamDownload(
                            fn () => print(Storage::disk('local')->get($record->caminho_arquivo)),
                            basename($record->caminho_arquivo),
                            ['Content-Type' => 'text/plain']
                        );
                    }),
                ViewAction::make(),
            ]);
    }
}
