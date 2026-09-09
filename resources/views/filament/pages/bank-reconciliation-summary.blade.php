<x-filament-panels::page>
    <form wire:submit="generate" class="space-y-4">
        {{ $this->form }}
        <x-filament::button type="submit">Comparar saldos</x-filament::button>
    </form>
    @if ($rows)
        <x-filament::section>
            <p class="mb-4 text-sm">O saldo bancário é derivado do saldo final informado pelo OFX. Dias sem saldo bancário permanecem sem comparação.</p>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead><tr><th class="p-2 text-left">Data</th><th class="p-2 text-right">Banco</th><th class="p-2 text-right">Sistema</th><th class="p-2 text-right">Diferença</th></tr></thead>
                    <tbody>
                    @foreach ($rows as $row)
                        <tr class="border-t">
                            <td class="p-2">{{ \Illuminate\Support\Carbon::parse($row['date'])->format('d/m/Y') }}</td>
                            <td class="p-2 text-right">{{ $row['bank'] === null ? 'Sem saldo no OFX' : 'R$ '.number_format((float) $row['bank'], 2, ',', '.') }}</td>
                            <td class="p-2 text-right">R$ {{ number_format((float) $row['system'], 2, ',', '.') }}</td>
                            <td class="p-2 text-right {{ $row['difference'] !== null && bccomp($row['difference'], '0', 2) !== 0 ? 'text-danger-600 font-bold' : 'text-success-600' }}">
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
