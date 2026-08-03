<td colspan="100" class="p-0">
    <div
        x-init="
            let refreshTimer;
            let refreshSummary = () => {
                clearTimeout(refreshTimer);
                refreshTimer = setTimeout(() => $wire.updateSelectedPayablesSummary(
                    [...selectedRecords],
                    [...deselectedRecords],
                    isTrackingDeselectedRecords
                ), 75);
            };
            $watch(() => selectedRecords.size, refreshSummary);
            $watch(() => deselectedRecords.size, refreshSummary);
            $watch('isTrackingDeselectedRecords', refreshSummary);
        "
        class="border-t border-primary-200 bg-primary-50 px-4 py-4 dark:border-primary-800 dark:bg-primary-950/40"
    >
        <div class="flex flex-col items-end justify-center gap-1 sm:flex-row sm:items-center sm:justify-end sm:gap-3">
            <span class="text-sm font-bold text-gray-700 dark:text-gray-200">
                {{ $quantidade }} {{ $quantidade === 1 ? 'conta selecionada' : 'contas selecionadas' }}
            </span>
            <span class="hidden text-primary-400 sm:inline">•</span>
            <span class="text-lg font-bold tabular-nums text-primary-700 dark:text-primary-300">
                Valor total: R$ {{ number_format($total, 2, ',', '.') }}
            </span>
        </div>
    </div>
</td>
