<?php

namespace App\Filament\Resources\Contracts\RelationManagers;

use App\Filament\Resources\Clients\RelationManagers\Nr1CyclesRelationManager as ClientNr1CyclesRelationManager;
use App\Filament\Support\Nr1CycleForm;
use App\Models\Nr1Cycle;
use App\Services\Nr1\Nr1CycleService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

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
            ->headerActions([
                Action::make('createNr1Cycle')
                    ->label('Criar ciclo NR-1')
                    ->icon('heroicon-o-plus')
                    ->modalHeading('Criar ciclo NR-1 manualmente')
                    ->form([
                        Select::make('reference_year')
                            ->label('Ano de referência')
                            ->options(function (): array {
                                $existingYears = $this->getOwnerRecord()->nr1Cycles()
                                    ->pluck('reference_year')
                                    ->map(fn ($year): int => (int) $year);
                                $lastYear = max(
                                    (int) now()->format('Y') + 10,
                                    ($existingYears->max() ?? (int) now()->format('Y')) + 1,
                                );

                                return collect(range($lastYear, 2000))
                                    ->reject(fn (int $year): bool => $existingYears->contains($year))
                                    ->mapWithKeys(fn (int $year): array => [$year => (string) $year])
                                    ->all();
                            })
                            ->default(fn (): int => max(
                                (int) now()->format('Y'),
                                ((int) $this->getOwnerRecord()->nr1Cycles()->max('reference_year')) + 1,
                            ))
                            ->helperText('Anos que já possuem um ciclo NR-1 não são exibidos.')
                            ->required()
                            ->native(false),
                    ])
                    ->action(function (array $data, Action $action): void {
                        try {
                            $cycle = app(Nr1CycleService::class)->createManually(
                                $this->getOwnerRecord(),
                                (int) $data['reference_year'],
                                (int) auth()->id(),
                            );
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Não foi possível criar o ciclo NR-1')
                                ->body(collect($exception->errors())->flatten()->first() ?? 'Verifique o ano informado.')
                                ->warning()
                                ->persistent()
                                ->send();

                            $action->halt();

                            return;
                        }

                        Notification::make()
                            ->title("Ciclo NR-1/{$cycle->reference_year} criado")
                            ->success()
                            ->send();
                    }),
            ])
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
