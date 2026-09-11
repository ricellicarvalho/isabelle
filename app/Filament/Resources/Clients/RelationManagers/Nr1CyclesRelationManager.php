<?php

namespace App\Filament\Resources\Clients\RelationManagers;

use App\Filament\Support\Nr1CycleForm;
use App\Models\Nr1Cycle;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;

class Nr1CyclesRelationManager extends RelationManager
{
    protected static string $relationship = 'nr1Cycles';

    protected static ?string $title = 'Histórico NR-1 por ano';

    protected static ?string $modelLabel = 'ciclo NR-1';

    protected static ?string $pluralModelLabel = 'ciclos NR-1';

    protected static string|\BackedEnum|null $icon = Heroicon::ClipboardDocumentCheck;

    protected static ?string $badge = 'Importante';

    protected static ?string $badgeColor = 'primary';

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('reference_year', 'desc')
            ->columns(self::columns())
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

    public static function columns(): array
    {
        return [
            TextColumn::make('reference_year')->label('Ano')->badge()->sortable(),
            TextColumn::make('status')->label('Status')->badge()
                ->color(fn (string $state): string => match ($state) {
                    'finalizada', 'regularizada' => 'success',
                    'em_andamento' => 'warning',
                    default => 'danger',
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'finalizada' => 'Finalizada', 'regularizada' => 'Regularizada',
                    'em_andamento' => 'Em andamento', default => 'Pendente',
                }),
            TextColumn::make('progress')->label('Progresso')
                ->state(fn (Nr1Cycle $record): string => $record->checklistProgresso().'%'),
            TextColumn::make('contract.numero')->label('Contrato')->placeholder('Vínculo legado pendente'),
        ];
    }
}
