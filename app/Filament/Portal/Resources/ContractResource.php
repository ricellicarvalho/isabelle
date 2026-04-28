<?php

namespace App\Filament\Portal\Resources;

use App\Filament\Portal\Resources\ContractResource\Pages\ListContracts;
use App\Filament\Portal\Resources\ContractResource\Pages\ViewContract;
use App\Models\Client;
use App\Models\Contract;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\ViewAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class ContractResource extends Resource
{
    protected static ?string $model = Contract::class;

    protected static ?string $modelLabel = 'contrato';

    protected static ?string $pluralModelLabel = 'contratos';

    protected static string|BackedEnum|null $navigationIcon = null;

    protected static string|UnitEnum|null $navigationGroup = 'Contratos';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $client = Client::where('portal_user_id', Auth::id())->first();

        return parent::getEloquentQuery()
            ->where('client_id', $client?->id);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Detalhes do Contrato')
                    ->columns(2)
                    ->components([
                        TextEntry::make('numero')->label('Número'),
                        TextEntry::make('tipo_servico')->label('Serviço'),
                        TextEntry::make('status')
                            ->label('Status')
                            ->badge()
                            ->color(fn (string $state): string => match ($state) {
                                'ativo'     => 'success',
                                'cancelado' => 'danger',
                                default     => 'warning',
                            }),
                        TextEntry::make('valor_total')->label('Valor Total')->money('BRL'),
                        TextEntry::make('data_inicio')->label('Início')->date('d/m/Y'),
                        TextEntry::make('data_fim')->label('Fim')->date('d/m/Y')->placeholder('Indeterminado'),
                        TextEntry::make('quantidade_parcelas')->label('Parcelas'),
                        TextEntry::make('forma_pagamento')->label('Forma de Pagamento'),
                        TextEntry::make('observacoes')->label('Observações')->columnSpanFull()->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('data_inicio', 'desc')
            ->columns([
                TextColumn::make('numero')->label('Nº Contrato')->searchable()->sortable(),
                TextColumn::make('tipo_servico')->label('Serviço')->limit(30),
                TextColumn::make('valor_total')->label('Valor Total')->money('BRL')->sortable(),
                TextColumn::make('quantidade_parcelas')->label('Parcelas'),
                TextColumn::make('data_inicio')->label('Início')->date('d/m/Y')->sortable(),
                TextColumn::make('data_fim')->label('Fim')->date('d/m/Y')->placeholder('Indeterminado'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ativo'     => 'success',
                        'cancelado' => 'danger',
                        default     => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'ativo'     => 'Ativo',
                        'cancelado' => 'Cancelado',
                        'rascunho'  => 'Rascunho',
                        default     => ucfirst($state),
                    }),
            ])
            ->actions([
                ViewAction::make(),
            ]);
    }

    public static function canCreate(): bool { return false; }
    public static function canEdit($record): bool { return false; }
    public static function canDelete($record): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index' => ListContracts::route('/'),
            'view'  => ViewContract::route('/{record}'),
        ];
    }
}
