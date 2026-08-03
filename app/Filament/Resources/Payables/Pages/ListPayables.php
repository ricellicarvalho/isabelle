<?php

namespace App\Filament\Resources\Payables\Pages;

use App\Filament\Resources\Payables\PayableResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPayables extends ListRecords
{
    protected static string $resource = PayableResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /**
     * @return array{quantidade: int, total: float}
     */
    public function getSelectedPayablesSummary(): array
    {
        $query = $this->getSelectedTableRecordsQuery(shouldFetchSelectedRecords: false);

        return [
            'quantidade' => (clone $query)->count(),
            'total' => (float) (clone $query)->sum('valor'),
        ];
    }

    /**
     * Sincroniza a seleção mantida pelo componente JavaScript da tabela.
     * O Filament só envia essa seleção ao servidor quando uma ação em lote é aberta.
     *
     * @param  array<int|string>  $selectedRecords
     * @param  array<int|string>  $deselectedRecords
     */
    public function updateSelectedPayablesSummary(array $selectedRecords, array $deselectedRecords, bool $trackingAll): void
    {
        $this->selectedTableRecords = $selectedRecords;
        $this->deselectedTableRecords = $deselectedRecords;
        $this->isTrackingDeselectedTableRecords = $trackingAll;
    }
}
