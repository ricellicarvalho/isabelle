<div class="space-y-5 text-sm">
    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div><dt class="font-medium text-gray-500">Cliente</dt><dd>{{ $version->client?->razao_social }}</dd></div>
        <div><dt class="font-medium text-gray-500">Serviço</dt><dd>{{ $version->tipo_servico }}</dd></div>
        <div><dt class="font-medium text-gray-500">Valor</dt><dd>R$ {{ number_format((float) $version->valor_total, 2, ',', '.') }}</dd></div>
        <div><dt class="font-medium text-gray-500">Pagamento</dt><dd>{{ $version->forma_pagamento }} — {{ $version->quantidade_parcelas }} parcela(s)</dd></div>
        <div><dt class="font-medium text-gray-500">Vigência</dt><dd>{{ $version->data_inicio?->format('d/m/Y') }} a {{ $version->data_fim?->format('d/m/Y') }}</dd></div>
        <div><dt class="font-medium text-gray-500">Registrada por</dt><dd>{{ $version->activatedBy?->name ?? '—' }}</dd></div>
    </dl>
    <div><div class="font-medium text-gray-500">Descrição</div><div>{{ $version->descricao ?: '—' }}</div></div>
    <div><div class="font-medium text-gray-500">Motivo</div><div>{{ $version->change_reason ?: '—' }}</div></div>
    <div><div class="font-medium text-gray-500">Observações</div><div>{{ $version->observacoes ?: '—' }}</div></div>
    @if ($version->changes->isNotEmpty())
        <div>
            <div class="mb-2 font-semibold">Alterações em relação à versão anterior</div>
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="w-full text-left">
                    <thead><tr class="bg-gray-50 dark:bg-gray-800"><th class="p-2">Campo</th><th class="p-2">Anterior</th><th class="p-2">Novo</th></tr></thead>
                    <tbody>@foreach ($version->changes as $change)<tr class="border-t border-gray-200 dark:border-gray-700"><td class="p-2">{{ $change->field }}</td><td class="p-2">{{ $change->old_value ?? '—' }}</td><td class="p-2">{{ $change->new_value ?? '—' }}</td></tr>@endforeach</tbody>
                </table>
            </div>
        </div>
    @endif
</div>
