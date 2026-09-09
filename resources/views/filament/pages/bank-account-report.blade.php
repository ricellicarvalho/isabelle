<x-filament-panels::page>
    <form wire:submit="generateReport" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit" wire:loading.attr="disabled">Gerar relatório</x-filament::button>
    </form>
    @if ($report)
        <x-filament::section>
            <div class="bank-account-report overflow-x-auto">
                @include('reports.bank-account-content')
            </div>
        </x-filament::section>
    @else
        <p>Selecione a conta e o período para consultar a movimentação.</p>
    @endif
    <style>
        .bank-account-report .account-heading { margin-bottom: 1rem; }
        .bank-account-report h2 { font-weight: 700; font-size: 1.1rem; }
        .bank-account-report .movements { width: 100%; font-size: .8rem; border-collapse: collapse; }
        .bank-account-report th, .bank-account-report td { padding: .6rem; text-align: left; border-bottom: 1px solid #9ca3af55; }
        .bank-account-report .money { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .bank-account-report .opening, .bank-account-report .report-note { margin: 1rem 0; }
        .bank-account-report .totals { margin: 1rem 0 0 auto; }
    </style>
</x-filament-panels::page>
