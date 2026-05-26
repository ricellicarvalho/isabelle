<x-filament-panels::page>
    <form wire:submit.prevent="generateReport">
        {{ $this->form }}
    </form>

    @php
        $tipoLabel = fn(string $tipo): string => match($tipo) {
            'nr1'          => 'NR-1',
            'palestra'     => 'Palestra',
            'consultoria'  => 'Consultoria',
            'treinamento'  => 'Treinamento',
            default        => 'Outro',
        };

        $diasBadge = function(int $dias): array {
            if ($dias <= 7)  return ['bg' => '#fef2f2', 'text' => '#dc2626', 'border' => '#fecaca', 'label' => "{$dias} dias"];
            if ($dias <= 15) return ['bg' => '#fffbeb', 'text' => '#d97706', 'border' => '#fde68a', 'label' => "{$dias} dias"];
            return             ['bg' => '#eff6ff', 'text' => '#2563eb', 'border' => '#bfdbfe', 'label' => "{$dias} dias"];
        };
    @endphp

    <x-filament::section>
        <x-slot name="heading">
            Contratos a vencer
            <span class="ml-2 text-sm font-normal text-gray-500">
                {{ count($contracts) }} {{ count($contracts) === 1 ? 'contrato encontrado' : 'contratos encontrados' }}
            </span>
        </x-slot>

        @if (count($contracts) === 0)
            <div class="py-12 text-center text-gray-400 text-sm">
                Nenhum contrato encontrado para os filtros selecionados.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                            <th class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300">Nº Contrato</th>
                            <th class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300">Cliente</th>
                            <th class="text-left py-3 px-2 font-semibold text-gray-700 dark:text-gray-300">Serviço</th>
                            <th class="text-right py-3 px-2 font-semibold text-gray-700 dark:text-gray-300">Valor</th>
                            <th class="text-center py-3 px-2 font-semibold text-gray-700 dark:text-gray-300">Encerramento</th>
                            <th class="text-center py-3 px-2 font-semibold text-gray-700 dark:text-gray-300">Dias Restantes</th>
                            <th class="py-3 px-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($contracts as $contract)
                            @php $badge = $diasBadge($contract['dias_restantes']); @endphp
                            <tr class="border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50 dark:hover:bg-white/5 transition">
                                <td class="py-3 px-2 font-mono font-medium">{{ $contract['numero'] }}</td>
                                <td class="py-3 px-2">{{ $contract['cliente'] }}</td>
                                <td class="py-3 px-2 text-gray-600 dark:text-gray-400">{{ $tipoLabel($contract['tipo_servico']) }}</td>
                                <td class="py-3 px-2 text-right tabular-nums">R$ {{ number_format($contract['valor_total'], 2, ',', '.') }}</td>
                                <td class="py-3 px-2 text-center tabular-nums">{{ $contract['data_fim'] }}</td>
                                <td class="py-3 px-2 text-center">
                                    <span style="display:inline-block;padding:2px 10px;border-radius:9999px;font-size:0.75rem;font-weight:600;background:{{ $badge['bg'] }};color:{{ $badge['text'] }};border:1px solid {{ $badge['border'] }};">
                                        {{ $badge['label'] }}
                                    </span>
                                </td>
                                <td class="py-3 px-2 text-right">
                                    <a href="{{ $contract['url'] }}" class="text-primary-600 hover:text-primary-700 text-xs font-medium">
                                        Abrir →
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
