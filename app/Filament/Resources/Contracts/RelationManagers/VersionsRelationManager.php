<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VersionsRelationManager extends RelationManager
{
    protected static string $relationship = 'versions';

    protected static ?string $title = 'Linha do tempo de renovações';

    protected static ?string $modelLabel = 'versão';

    protected static ?string $pluralModelLabel = 'versões';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('version_number', 'desc')
            ->columns([
                TextColumn::make('version_number')->label('Versão')->formatStateUsing(fn ($state) => "v{$state}")->badge(),
                TextColumn::make('change_type')->label('Tipo')->formatStateUsing(fn (string $state): string => match ($state) {
                    'original' => 'Original', 'renewal' => 'Renovação', 'amendment' => 'Aditivo', 'correction' => 'Correção',
                }),
                TextColumn::make('status')->label('Situação')->badge()->color(fn (string $state): string => match ($state) {
                    'active' => 'success', 'superseded' => 'gray', 'cancelled' => 'danger', default => 'warning',
                })->formatStateUsing(fn (string $state): string => match ($state) {
                    'active' => 'Vigente', 'superseded' => 'Encerrada', 'cancelled' => 'Cancelada', 'draft' => 'Rascunho',
                }),
                TextColumn::make('client.razao_social')->label('Cliente')->limit(30),
                TextColumn::make('valor_total')->label('Valor')->money('BRL'),
                TextColumn::make('data_inicio')->label('Início')->date('d/m/Y'),
                TextColumn::make('data_fim')->label('Fim')->date('d/m/Y'),
                TextColumn::make('change_reason')->label('Motivo')->wrap()->limit(60),
                TextColumn::make('activatedBy.name')->label('Responsável')->placeholder('—'),
                TextColumn::make('activated_at')->label('Registrada em')->dateTime('d/m/Y H:i'),
            ])
            ->headerActions([])
            ->actions([
                Action::make('details')->label('Ver detalhes')->icon('heroicon-o-eye')
                    ->modalHeading(fn ($record): string => "Contrato {$record->numero} — Versão {$record->version_number}")
                    ->modalWidth('3xl')->modalSubmitAction(false)
                    ->modalContent(fn ($record) => view('filament.contracts.version-details', ['version' => $record->load('changes')])),
                Action::make('download')->label('Baixar PDF')->icon('heroicon-o-document-arrow-down')
                    ->visible(fn ($record): bool => filled($record->arquivo_pdf))
                    ->action(fn ($record): BinaryFileResponse => response()->download(
                        Storage::disk('local')->path($record->arquivo_pdf),
                        "{$record->numero}_v{$record->version_number}.pdf",
                        ['Content-Type' => 'application/pdf']
                    )),
            ])
            ->bulkActions([]);
    }
}
