<x-filament-panels::page>
    <form wire:submit="generate" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit">Comparar saldos</x-filament::button>
    </form>
    @if ($rows)
        <x-filament::section>
            <p class="mb-4 text-sm">O saldo bancário é derivado do saldo final informado pelo OFX. Dias sem saldo bancário permanecem sem comparação.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" style="width: 100%; min-width: 720px; table-layout: fixed">
                    <colgroup>
                        <col style="width: 22%">
                        <col style="width: 26%">
                        <col style="width: 26%">
                        <col style="width: 26%">
                    </colgroup>
                    <thead class="bg-gray-50 dark:bg-white/5">
                        <tr>
                            <th class="p-3 text-left font-semibold">Data</th>
                            <th class="p-3 text-right font-semibold" style="text-align: right">Banco</th>
                            <th class="p-3 text-right font-semibold" style="text-align: right">Sistema</th>
                            <th class="p-3 text-right font-semibold" style="text-align: right">Diferença</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-t border-gray-200 dark:border-white/10">
                            <td class="p-3 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td class="p-3 text-right tabular-nums whitespace-nowrap" style="text-align: right">{{ $row['bank'] === null ? 'Sem saldo no OFX' : 'R$ '.number_format((float) $row['bank'], 2, ',', '.') }}</td>
                            <td class="p-3 text-right tabular-nums whitespace-nowrap" style="text-align: right">R$ {{ number_format((float) $row['system'], 2, ',', '.') }}</td>
                            <td class="p-3 text-right tabular-nums whitespace-nowrap {{ $row['difference'] !== null && bccomp($row['difference'], '0', 2) !== 0 ? 'text-danger-600 font-bold' : 'text-success-600' }}" style="text-align: right">
                                {{ $row['difference'] === null ? '—' : 'R$ '.number_format((float) $row['difference'], 2, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
