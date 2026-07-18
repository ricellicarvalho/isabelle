<x-filament-panels::page>
    <form wire:submit.prevent="generateReport">
        {{ $this->form }}
    </form>

    @php
        $statusLabel = fn (string $status): string => match ($status) {
            'pendente' => 'Pendente',
            'pago' => 'Pago',
            'cancelado' => 'Cancelado',
            'vencido' => 'Vencido',
            default => $status,
        };

        $statusColor = fn (string $status): string => match ($status) {
            'pago' => 'color:#15803d;background:#dcfce7;',
            'cancelado' => 'color:#4b5563;background:#f3f4f6;',
            'vencido' => 'color:#b91c1c;background:#fee2e2;',
            default => 'color:#a16207;background:#fef9c3;',
        };
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            Contas a Pagar
            <span class="ml-2 text-sm font-normal text-gray-500">
                {{ $report['count'] ?? 0 }} {{ ($report['count'] ?? 0) === 1 ? 'registro encontrado' : 'registros encontrados' }}
            </span>
        </x-slot>

        <div class="mb-5 rounded-lg bg-primary-50 p-4 dark:bg-primary-950/30">
            <div class="text-sm text-gray-500 dark:text-gray-400">Valor total das contas a pagar</div>
            <div class="mt-1 text-2xl font-bold text-primary-700 dark:text-primary-300">
                R$ {{ number_format($report['total'] ?? 0, 2, ',', '.') }}
            </div>
        </div>

        @if (empty($report['items']))
            <div class="py-12 text-center text-sm text-gray-400">
                Nenhuma conta a pagar encontrada para os filtros selecionados.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="px-2 py-3 text-left font-semibold">Fornecedor</th>
                            <th class="px-2 py-3 text-left font-semibold">Categoria</th>
                            <th class="px-2 py-3 text-left font-semibold">Descrição</th>
                            <th class="px-2 py-3 text-center font-semibold">Vencimento</th>
                            <th class="px-2 py-3 text-center font-semibold">Status</th>
                            <th class="px-2 py-3 text-right font-semibold">Valor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['items'] as $item)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="px-2 py-3">{{ $item['fornecedor'] }}</td>
                                <td class="px-2 py-3">{{ $item['categoria'] }}</td>
                                <td class="px-2 py-3">{{ $item['descricao'] }}</td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ $item['vencimento'] }}</td>
                                <td class="px-2 py-3 text-center">
                                    <span style="{{ $statusColor($item['status']) }}display:inline-block;padding:2px 9px;border-radius:9999px;font-size:.75rem;font-weight:600;">
                                        {{ $statusLabel($item['status']) }}
                                    </span>
                                </td>
                                <td class="px-2 py-3 text-right tabular-nums">R$ {{ number_format($item['valor'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-primary-600 font-bold">
                            <td colspan="5" class="px-2 py-3">Total geral</td>
                            <td class="px-2 py-3 text-right">R$ {{ number_format($report['total'] ?? 0, 2, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
