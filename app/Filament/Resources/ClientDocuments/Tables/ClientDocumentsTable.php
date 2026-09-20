<?php

namespace App\Filament\Resources\ClientDocuments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('razao_social', 'asc')
            ->columns([
                TextColumn::make('razao_social')
                    ->label('Cliente')
                    ->searchable()
                    ->sortable(query: fn (Builder $query, string $direction): Builder => $query->orderByRaw('TRIM(clients.razao_social) '.$direction))
                    ->url(fn (\App\Models\Client $record): string => route('filament.admin.resources.client-documents.manage', ['record' => $record->getKey()])),

                TextColumn::make('total_arquivos')
                    ->label('Arquivos')
                    ->sortable()
                    ->badge()
                    ->color('info'),

                TextColumn::make('client_documents_max_created_at')
                    ->label('Último documento')
                    ->date('d/m/Y')
                    ->sortable()
                    ->placeholder('—'),
            ]);
    }
}
