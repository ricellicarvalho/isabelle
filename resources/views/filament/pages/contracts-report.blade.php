<x-filament-panels::page>
    <style>
        .contracts-summary-card {
            border-width: 1px !important;
        }
        .contracts-summary-card-total {
            background: #f5f3ff !important;
            border-color: #c4b5fd !important;
        }
        .contracts-summary-card-new {
            background: #f0fdf4 !important;
            border-color: #86efac !important;
        }
        .contracts-summary-card-renewal {
            background: #eff6ff !important;
            border-color: #93c5fd !important;
        }
        .contracts-summary-value-total { color: #6d28d9 !important; }
        .contracts-summary-value-new { color: #15803d !important; }
        .contracts-summary-value-renewal { color: #1d4ed8 !important; }
        .contracts-movement-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 5.5rem;
            border-radius: 9999px;
            padding: 0.25rem 0.75rem;
            font-size: 0.75rem;
            font-weight: 800;
            line-height: 1rem;
        }
        .contracts-movement-badge-new {
            background: #dcfce7;
            border: 1px solid #86efac;
            color: #166534;
        }
        .contracts-movement-badge-renewal {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            color: #1e40af;
        }
        .dark .contracts-summary-card-total { background: rgba(76, 29, 149, 0.3) !important; border-color: #7c3aed !important; }
        .dark .contracts-summary-card-new { background: rgba(20, 83, 45, 0.3) !important; border-color: #16a34a !important; }
        .dark .contracts-summary-card-renewal { background: rgba(30, 58, 138, 0.3) !important; border-color: #2563eb !important; }
        .dark .contracts-summary-value-total { color: #c4b5fd !important; }
        .dark .contracts-summary-value-new { color: #86efac !important; }
        .dark .contracts-summary-value-renewal { color: #93c5fd !important; }
        .dark .contracts-movement-badge-new { background: #14532d; border-color: #22c55e; color: #dcfce7; }
        .dark .contracts-movement-badge-renewal { background: #1e3a8a; border-color: #3b82f6; color: #dbeafe; }
    </style>

    <form>
        {{ $this->form }}
    </form>

    @php
        $movementLabel = fn (string $type): string => $type === 'renewal' ? 'Renovado' : 'Novo';
        $serviceLabel = fn (string $type): string => match ($type) {
            'nr1' => 'NR-1',
            'palestra' => 'Palestra',
            'consultoria' => 'Consultoria',
            'treinamento' => 'Treinamento',
            default => 'Outro',
        };
    @endphp

    <div class="grid gap-4 sm:grid-cols-3">
        <x-filament::section class="contracts-summary-card contracts-summary-card-total">
            <div class="text-sm text-gray-500 dark:text-gray-400">Total de movimentações</div>
            <div class="contracts-summary-value-total mt-1 text-2xl font-bold">{{ $report['count'] }}</div>
        </x-filament::section>
        <x-filament::section class="contracts-summary-card contracts-summary-card-new">
            <div class="text-sm text-gray-500 dark:text-gray-400">Novos contratos</div>
            <div class="contracts-summary-value-new mt-1 text-2xl font-bold">{{ $report['new_count'] }}</div>
        </x-filament::section>
        <x-filament::section class="contracts-summary-card contracts-summary-card-renewal">
            <div class="text-sm text-gray-500 dark:text-gray-400">Contratos renovados</div>
            <div class="contracts-summary-value-renewal mt-1 text-2xl font-bold">{{ $report['renewal_count'] }}</div>
        </x-filament::section>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Movimentação de contratos
            <span class="ml-2 text-sm font-normal text-gray-500">
                {{ $report['count'] }} {{ $report['count'] === 1 ? 'registro encontrado' : 'registros encontrados' }}
            </span>
        </x-slot>

        @if (empty($report['items']))
            <div class="py-12 text-center text-sm text-gray-400">
                Nenhum contrato encontrado para os filtros selecionados.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="px-2 py-3 text-center font-semibold">Movimentação</th>
                            <th class="px-2 py-3 text-center font-semibold">Data</th>
                            <th class="px-2 py-3 text-left font-semibold">Contrato</th>
                            <th class="px-2 py-3 text-left font-semibold">Cliente</th>
                            <th class="px-2 py-3 text-left font-semibold">Serviço</th>
                            <th class="px-2 py-3 text-center font-semibold">Versão</th>
                            <th class="px-2 py-3 text-center font-semibold">Vigência</th>
                            <th class="px-2 py-3 text-right font-semibold">Valor</th>
                            <th class="px-2 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['items'] as $item)
                            <tr class="border-b border-gray-100 transition hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                <td class="px-2 py-3 text-center">
                                    <span @class([
                                        'contracts-movement-badge',
                                        'contracts-movement-badge-new' => $item['movement_type'] === 'new',
                                        'contracts-movement-badge-renewal' => $item['movement_type'] === 'renewal',
                                    ])>{{ $movementLabel($item['movement_type']) }}</span>
                                </td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ $item['movement_date'] }}</td>
                                <td class="px-2 py-3 font-mono">{{ $item['numero'] }}</td>
                                <td class="px-2 py-3">{{ $item['cliente'] }}</td>
                                <td class="px-2 py-3">{{ $serviceLabel($item['tipo_servico']) }}</td>
                                <td class="px-2 py-3 text-center">{{ $item['version_number'] }}</td>
                                <td class="px-2 py-3 text-center tabular-nums">{{ $item['data_inicio'] }} a {{ $item['data_fim'] }}</td>
                                <td class="px-2 py-3 text-right tabular-nums">R$ {{ number_format($item['valor_total'], 2, ',', '.') }}</td>
                                <td class="px-2 py-3 text-right">
                                    <a href="{{ $item['url'] }}" class="text-xs font-medium text-primary-600 hover:text-primary-700">Abrir →</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-primary-600 font-bold">
                            <td colspan="7" class="px-2 py-3">Valor total das movimentações</td>
                            <td class="px-2 py-3 text-right">R$ {{ number_format($report['total_value'], 2, ',', '.') }}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
