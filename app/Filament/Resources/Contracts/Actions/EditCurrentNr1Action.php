<?php

namespace App\Filament\Resources\Contracts\Actions;

use App\Filament\Support\Nr1CycleForm;
use App\Models\Contract;
use App\Models\Nr1Cycle;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class EditCurrentNr1Action
{
    public static function make(): Action
    {
        return Action::make('editCurrentNr1')
            ->label(fn (Contract $record): string => 'Preencher checklist NR-1/'.($record->nr1Cycles()->max('reference_year') ?? ''))
            ->icon('heroicon-o-clipboard-document-check')
            ->color('primary')
            ->visible(fn (Contract $record): bool => $record->tipo_servico === 'nr1' && $record->nr1Cycles()->exists())
            ->modalHeading(fn (Contract $record): string => 'Checklist NR-1/'.$record->nr1Cycles()->max('reference_year'))
            ->modalDescription('Preencha o checklist do ciclo atual. Os anos anteriores permanecem preservados no histórico.')
            ->modalWidth('5xl')
            ->fillForm(function (Contract $record): array {
                $cycle = self::currentCycle($record);

                return $cycle?->only([
                    'reference_year', 'status', 'contract_id', 'opened_by_contract_version_id', 'checklist', 'notes',
                ]) ?? [];
            })
            ->form(Nr1CycleForm::schema())
            ->action(function (array $data, Contract $record): void {
                $cycle = self::currentCycle($record);

                abort_unless($cycle, 404);

                $cycle->update($data + ['updated_by' => auth()->id()]);

                Notification::make()
                    ->title("Checklist NR-1/{$cycle->reference_year} atualizado")
                    ->success()
                    ->send();
            });
    }

    private static function currentCycle(Contract $contract): ?Nr1Cycle
    {
        return $contract->nr1Cycles()->latest('reference_year')->latest('id')->first();
    }
}
