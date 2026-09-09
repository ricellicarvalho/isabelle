<div class="space-y-4">
    @foreach ($settlements as $settlement)
        <div class="rounded-lg border p-4 space-y-1">
            <p><strong>#{{ $settlement->id }} · {{ $settlement->settled_at->format('d/m/Y') }} · {{ $settlement->bankAccount->display_name }}</strong></p>
            <p>{{ $settlement->status === 'confirmed' ? 'Confirmada' : 'Estornada' }} · Principal: R$ {{ $settlement->principal_amount }} · Movimento: R$ {{ $settlement->amount }}</p>
            <p>Juros: {{ $settlement->interest }} · Multa: {{ $settlement->penalty }} · Desconto: {{ $settlement->discount }} · Tarifa: {{ $settlement->fee }}</p>
            <p>Documento: {{ $settlement->reference ?: '—' }} · Forma: {{ $settlement->payment_method ?: '—' }}</p>
            <p>Registrada por: {{ $settlement->creator?->name ?: 'Legado' }} · {{ $settlement->created_at->format('d/m/Y H:i') }}</p>
            @if ($settlement->notes)<p>{{ $settlement->notes }}</p>@endif
            @if ($settlement->reversed_at)
                <p>Estorno: {{ $settlement->reversed_at->format('d/m/Y H:i') }} · {{ $settlement->reversedBy?->name }} · {{ $settlement->reversal_reason }}</p>
            @endif
        </div>
    @endforeach
</div>
