<?php

namespace App\Filament\Resources\BankRetornos\Tables;

use App\Models\BankRetorno;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BankRetornosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('data_processamento', 'desc')
            ->columns([
                TextColumn::make('nome_arquivo')
                    ->label('Arquivo')
                    ->searchable()
                    ->limit(40),

                TextColumn::make('banco')
                    ->label('Banco')
                    ->placeholder('—'),

                TextColumn::make('data_processamento')
                    ->label('Processado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('quantidade_titulos')
                    ->label('Títulos')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('quantidade_liquidados')
                    ->label('Liquidados')
                    ->numeric()
                    ->color('success'),

                TextColumn::make('quantidade_baixados')
                    ->label('Baixados')
                    ->numeric()
                    ->color('warning'),

                TextColumn::make('quantidade_erros')
                    ->label('Erros')
                    ->numeric()
                    ->color('danger'),

                TextColumn::make('quantidade_nao_encontrados')
                    ->label('Não encontrados')
                    ->numeric()
                    ->color('gray'),

                TextColumn::make('valor_total')
                    ->label('Valor Liquidado')
                    ->money('BRL')
                    ->sortable(),
            ])
            ->actions([
                Action::make('baixarArquivo')
                    ->label('Baixar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('info')
                    ->visible(fn (BankRetorno $record): bool => $record->caminho_arquivo && Storage::disk('local')->exists($record->caminho_arquivo))
                    ->action(function (BankRetorno $record): StreamedResponse {
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
