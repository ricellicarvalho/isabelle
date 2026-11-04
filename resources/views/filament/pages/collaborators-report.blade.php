<x-filament-panels::page>
    <style>
        .collaborators-stat-card {
            position: relative;
            overflow: hidden;
            min-height: 8.5rem;
            border: 1px solid transparent;
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            box-shadow: 0 12px 28px -16px rgba(30, 41, 59, 0.55);
        }
        .collaborators-stat-card::after {
            content: '';
            position: absolute;
            right: -2.5rem;
            bottom: -3.5rem;
            width: 10rem;
            height: 10rem;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.16);
        }
        .collaborators-stat-people {
            color: #fff;
            border-color: #7c3aed;
            background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 55%, #a855f7 100%);
        }
        .collaborators-stat-clients {
            color: #fff;
            border-color: #0891b2;
            background: linear-gradient(135deg, #155e75 0%, #0891b2 55%, #22d3ee 100%);
        }
        .collaborators-stat-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.75rem;
            height: 2.75rem;
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 0.875rem;
            background: rgba(255, 255, 255, 0.17);
            backdrop-filter: blur(4px);
        }
        .collaborators-stat-icon .fi-icon { width: 1.5rem; height: 1.5rem; }
        .collaborators-client-card {
            border-color: #c4b5fd !important;
            border-radius: 0.875rem !important;
            box-shadow: 0 8px 24px -18px rgba(76, 29, 149, 0.65);
        }
        .collaborators-client-header {
            color: #5f3a96;
            background: linear-gradient(110deg, #faf7ff 0%, #f3edfc 58%, #ebe1fa 100%);
            border-bottom: 1px solid #d9c6f2;
        }
        .collaborators-client-icon {
            color: #7546b8;
            background: rgba(135, 81, 212, 0.12);
        }
        .collaborators-client-name {
            color: #5f3a96;
        }
        .collaborators-client-count {
            display: inline-flex;
            align-items: center;
            border: 1px solid #d2b9ee;
            border-radius: 9999px;
            padding: 0.25rem 0.65rem;
            color: #68409f;
            background: rgba(135, 81, 212, 0.09);
            font-size: 0.75rem;
            font-weight: 700;
            white-space: nowrap;
        }
        .collaborators-client-link {
            border-radius: 0.5rem;
            padding: 0.45rem 0.7rem;
            color: #68409f;
            border: 1px solid #d2b9ee;
            background: rgba(255, 255, 255, 0.65);
            transition: background 150ms ease;
        }
        .collaborators-client-link:hover { color: #4c267f; background: #fff; }
        .dark .collaborators-client-card { border-color: #6d28d9 !important; }
        .dark .collaborators-client-header {
            color: #e9d5ff;
            background: linear-gradient(110deg, rgba(76, 29, 149, 0.32), rgba(88, 28, 135, 0.24));
            border-bottom-color: #6d28d9;
        }
        .dark .collaborators-client-icon { color: #ddd6fe; background: rgba(196, 181, 253, 0.14); }
        .dark .collaborators-client-name { color: #ede9fe; }
        .dark .collaborators-client-count { color: #ede9fe; border-color: #7c3aed; background: rgba(124, 58, 237, 0.18); }
        .dark .collaborators-client-link { color: #ddd6fe; border-color: #7c3aed; background: rgba(124, 58, 237, 0.14); }
        .dark .collaborators-client-link:hover { color: #fff; background: rgba(124, 58, 237, 0.28); }
    </style>

    <form>
        {{ $this->form }}
    </form>

    <div class="grid gap-4 sm:grid-cols-2">
        <div class="collaborators-stat-card collaborators-stat-people">
            <div class="relative z-10 flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold tracking-wide text-white/80">Colaboradores encontrados</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ $report['count'] }}</div>
                    <div class="mt-2 text-xs font-medium text-white/70">Pessoas exibidas nos filtros atuais</div>
                </div>
                <span class="collaborators-stat-icon">
                    <x-filament::icon icon="heroicon-o-user-group" />
                </span>
            </div>
        </div>
        <div class="collaborators-stat-card collaborators-stat-clients">
            <div class="relative z-10 flex items-start justify-between gap-4">
                <div>
                    <div class="text-sm font-semibold tracking-wide text-white/80">Clientes representados</div>
                    <div class="mt-2 text-4xl font-black leading-none">{{ $report['clients_count'] }}</div>
                    <div class="mt-2 text-xs font-medium text-white/70">Empresas presentes no resultado</div>
                </div>
                <span class="collaborators-stat-icon">
                    <x-filament::icon icon="heroicon-o-building-office-2" />
                </span>
            </div>
        </div>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            Colaboradores
            <span class="ml-2 text-sm font-normal text-gray-500">
                {{ $report['filters']['cliente'] ?? 'Todos os clientes' }}
            </span>
        </x-slot>

        @if (empty($report['items']))
            <div class="py-12 text-center text-sm text-gray-400">
                Nenhum colaborador encontrado para os filtros selecionados.
            </div>
        @else
            <div class="overflow-x-auto">
                @foreach (collect($report['items'])->groupBy('client_id') as $clientItems)
                    @php($firstItem = $clientItems->first())
                    <div class="collaborators-client-card mb-6 overflow-hidden border last:mb-0">
                        <div class="collaborators-client-header flex items-center gap-4 px-5 py-4">
                            <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3">
                                <span class="collaborators-client-icon flex h-9 w-9 shrink-0 items-center justify-center rounded-lg">
                                    <x-filament::icon icon="heroicon-o-building-office" class="h-5 w-5" />
                                </span>
                                <span class="collaborators-client-name text-base font-extrabold tracking-wide">{{ $firstItem['cliente'] }}</span>
                                <span class="collaborators-client-count">
                                    {{ $clientItems->count() }} {{ $clientItems->count() === 1 ? 'colaborador' : 'colaboradores' }}
                                </span>
                            </div>
                            <a href="{{ $firstItem['client_url'] }}" target="_blank" rel="noopener noreferrer" class="collaborators-client-link ml-auto shrink-0 text-xs font-bold">
                                Abrir cliente ↗
                            </a>
                        </div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b-2 border-gray-200 dark:border-gray-700">
                                    <th class="px-3 py-3 text-left font-semibold">Colaborador</th>
                                    <th class="px-3 py-3 text-left font-semibold">Telefone</th>
                                    <th class="px-3 py-3 text-left font-semibold">Local / Setor</th>
                                    <th class="px-3 py-3 text-center font-semibold">Cadastrado em</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($clientItems as $item)
                                    <tr class="border-b border-gray-100 transition last:border-b-0 hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-white/5">
                                        <td class="px-3 py-3 font-medium">{{ $item['nome'] }}</td>
                                        <td class="px-3 py-3">{{ $item['telefone'] ?: '—' }}</td>
                                        <td class="px-3 py-3">{{ $item['local'] ?: '—' }}</td>
                                        <td class="px-3 py-3 text-center tabular-nums">{{ $item['cadastrado_em'] ?: '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
