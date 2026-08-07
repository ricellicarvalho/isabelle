<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Remessa 5
        </x-slot>

        <div class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Boletos</div>
                <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ $totalBoletos }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Clientes</div>
                <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">{{ count($grupos) }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500 dark:text-gray-400">Valor total</div>
                <div class="mt-1 text-2xl font-bold text-gray-950 dark:text-white">R$ {{ number_format($valorTotal, 2, ',', '.') }}</div>
            </div>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            Clientes
        </x-slot>

        @if (empty($grupos))
            <div class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                Nenhum boleto encontrado para reenvio.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-gray-700">
                            <th class="px-3 py-3 text-left font-semibold">Cliente</th>
                            <th class="px-3 py-3 text-center font-semibold">Boletos</th>
                            <th class="px-3 py-3 text-center font-semibold">Vencimentos</th>
                            <th class="px-3 py-3 text-right font-semibold">Valor</th>
                            <th class="px-3 py-3 text-right font-semibold">Ação</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grupos as $grupo)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-3 py-3 font-medium text-gray-950 dark:text-white">{{ $grupo['cliente'] }}</td>
                                <td class="px-3 py-3 text-center tabular-nums">{{ $grupo['quantidade'] }}</td>
                                <td class="px-3 py-3 text-center tabular-nums">
                                    {{ $grupo['primeiro_vencimento'] ? \Illuminate\Support\Carbon::parse($grupo['primeiro_vencimento'])->format('d/m/Y') : '—' }}
                                    a
                                    {{ $grupo['ultimo_vencimento'] ? \Illuminate\Support\Carbon::parse($grupo['ultimo_vencimento'])->format('d/m/Y') : '—' }}
                                </td>
                                <td class="px-3 py-3 text-right tabular-nums">R$ {{ number_format($grupo['valor_total'], 2, ',', '.') }}</td>
                                <td class="px-3 py-3 text-right">
                                    @if ($grupo['client_id'])
                                        <x-filament::button
                                            icon="heroicon-o-document-arrow-down"
                                            color="danger"
                                            size="sm"
                                            wire:click="baixarCliente({{ $grupo['client_id'] }})"
                                        >
                                            Baixar PDF
                                        </x-filament::button>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
