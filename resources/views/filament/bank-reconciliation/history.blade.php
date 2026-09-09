<div class="space-y-4">
    <div>
        <h3 class="font-bold">Alocações</h3>
        @forelse ($entry->allocations as $allocation)
            <p>#{{ $allocation->id }} · R$ {{ $allocation->amount }} · {{ $allocation->movement?->description }} · {{ $allocation->reversed_at ? 'Desfeita em '.$allocation->reversed_at->format('d/m/Y H:i').' — '.$allocation->reversal_reason : 'Ativa' }}</p>
        @empty
            <p>Sem alocações.</p>
        @endforelse
    </div>
    <div>
        <h3 class="font-bold">Eventos</h3>
        @foreach ($entry->events as $event)
            <p>{{ $event->created_at->format('d/m/Y H:i') }} · {{ $event->action }} · {{ $event->createdBy?->name ?: 'Sistema' }}{{ $event->reason ? ' — '.$event->reason : '' }}</p>
        @endforeach
    </div>
</div>
