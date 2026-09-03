<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Filament\Resources\Clients\RelationManagers\Nr1CyclesRelationManager as ClientNr1CyclesRelationManager;
use App\Filament\Support\Nr1CycleForm;
use App\Models\Nr1Cycle;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;

class Nr1CyclesRelationManager extends RelationManager
{
    protected static string $relationship = 'nr1Cycles';

    protected static ?string $title = 'Checklist NR-1 por ano';

    protected static string|\BackedEnum|null $icon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $badge = 'Importante';

    protected static ?string $badgeColor = 'primary';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->tipo_servico === 'nr1';
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('reference_year', 'desc')
            ->columns(ClientNr1CyclesRelationManager::columns())
            ->headerActions([])
            ->actions([
                EditAction::make()
                    ->label('Editar checklist')
                    ->modalHeading(fn (Nr1Cycle $record): string => "NR-1/{$record->reference_year}")
                    ->modalWidth('5xl')
                    ->form(Nr1CycleForm::schema())
                    ->mutateDataUsing(function (array $data): array {
                        $data['updated_by'] = auth()->id();

                        return $data;
                    }),
            ])
            ->bulkActions([]);
    }
}
