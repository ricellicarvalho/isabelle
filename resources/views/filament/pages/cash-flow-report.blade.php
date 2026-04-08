<x-filament-panels::page>
    <form wire:submit.prevent="generateReport">
        {{ $this->form }}
    </form>

    @if ($report)
        @php $totais = $report['totais']; @endphp

        <x-filament::section>
            <x-slot name="heading">
                Período: {{ $report['periodo']['inicio']->format('d/m/Y') }} a {{ $report['periodo']['fim']->format('d/m/Y') }}
                — Regime: {{ $report['regime'] === 'caixa' ? 'Caixa' : 'Competência' }}
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="rounded-lg border border-gray-200 bg-gray-50 dark:bg-gray-900 p-4">
                    <div class="text-xs text-gray-600 dark:text-gray-400 uppercase">Saldo Inicial</div>
                    <div class="text-2xl font-bold tabular-nums">R$ {{ number_format($report['saldo_inicial'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-success-200 bg-success-50 dark:bg-success-950/30 p-4">
                    <div class="text-xs text-success-700 dark:text-success-300 uppercase">Entradas</div>
                    <div class="text-2xl font-bold text-success-700 dark:text-success-300 tabular-nums">R$ {{ number_format($totais['entradas'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border border-danger-200 bg-danger-50 dark:bg-danger-950/30 p-4">
                    <div class="text-xs text-danger-700 dark:text-danger-300 uppercase">Saídas</div>
                    <div class="text-2xl font-bold text-danger-700 dark:text-danger-300 tabular-nums">R$ {{ number_format($totais['saidas'], 2, ',', '.') }}</div>
                </div>
                <div class="rounded-lg border {{ $totais['saldo_final'] >= 0 ? 'border-info-200 bg-info-50 dark:bg-info-950/30' : 'border-danger-200 bg-danger-50 dark:bg-danger-950/30' }} p-4">
                    <div class="text-xs uppercase {{ $totais['saldo_final'] >= 0 ? 'text-info-700 dark:text-info-300' : 'text-danger-700 dark:text-danger-300' }}">Saldo Final</div>
                    <div class="text-2xl font-bold tabular-nums {{ $totais['saldo_final'] >= 0 ? 'text-info-700 dark:text-info-300' : 'text-danger-700 dark:text-danger-300' }}">R$ {{ number_format($totais['saldo_final'], 2, ',', '.') }}</div>
                </div>
            </div>

            @if (count($report['linhas']) === 0)
                <p class="text-center text-gray-500 py-8">Nenhuma movimentação no período.</p>
            @else
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b-2 border-gray-300 dark:border-gray-600">
                            <th class="text-left py-2">Data</th>
                            <th class="text-left py-2">Descrição</th>
                            <th class="text-left py-2">Categoria</th>
                            <th class="text-center py-2">Tipo</th>
                            <th class="text-right py-2">Valor</th>
                            <th class="text-right py-2">Saldo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($report['linhas'] as $linha)
                            <tr class="border-b border-gray-100 dark:border-gray-800">
                                <td class="py-2">{{ $linha['data']?->format('d/m/Y') }}</td>
                                <td class="py-2">{{ $linha['descricao'] }}</td>
                                <td class="py-2 text-gray-500">{{ $linha['categoria'] }}</td>
                                <td class="py-2 text-center">
                                    @if ($linha['tipo'] === 'entrada')
                                        <span class="text-success-600">↑ Entrada</span>
                                    @else
                                        <span class="text-danger-600">↓ Saída</span>
                                    @endif
                                </td>
                                <td class="py-2 text-right tabular-nums {{ $linha['tipo'] === 'entrada' ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $linha['tipo'] === 'entrada' ? '+' : '-' }} R$ {{ number_format($linha['valor'], 2, ',', '.') }}
                                </td>
                                <td class="py-2 text-right tabular-nums font-medium">R$ {{ number_format($linha['saldo_acumulado'], 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
