@php $money = fn ($value) => number_format((float) $value, 2, ',', '.'); @endphp
<div class="account-heading">
    <h2>{{ $report['company'] }} — {{ $report['account']['name'] }}</h2>
    <p>{{ $report['account']['bank'] }} · Agência {{ $report['account']['agency'] }} · Conta {{ $report['account']['number'] }} · Limite R$ {{ $money($report['account']['credit_limit']) }}</p>
    <p>Período: {{ $report['period']['start'] }} a {{ $report['period']['end'] }}</p>
    <p>Categoria: {{ $report['filters']['category'] ?? 'Todas' }} · Conciliação: {{ match ($report['filters']['reconciliation'] ?? '') { 'pending' => 'Pendente', 'reconciled' => 'Conciliado', default => 'Todas' } }} · Busca: {{ $report['filters']['search'] ?? '—' }}</p>
</div>
@if ($report['unclassified'])
    <p class="report-note">{{ $report['unclassified'] }} título(s) pago(s) desta conta ainda sem baixa bancária e fora dos totais. Revise os títulos para completar o saldo.</p>
@endif
@if ($report['filtered'])
    <p class="report-note">Os filtros selecionam linhas. Saldos e totais da conta incluem todos os movimentos confirmados do período.</p>
@endif
<p class="opening">Saldo anterior: <strong>R$ {{ $money($report['opening']) }}</strong></p>
<table class="movements">
    <thead><tr><th>Lançamento</th><th>Documento/Referência</th><th>Débito</th><th>Crédito</th><th>Histórico</th><th>Favorecido/Cliente</th><th>Categoria</th><th>Conciliação</th><th>Saldo da conta</th></tr></thead>
    <tbody>
        @forelse ($report['rows'] as $row)
            <tr>
                <td>{{ $row['data'] }}</td><td>{{ $row['reference'] ?: '—' }}</td>
                <td class="money debit">{{ $money($row['debit']) }}</td><td class="money credit">{{ $money($row['credit']) }}</td>
                <td>{{ $row['description'] }}</td><td>{{ $row['counterparty'] ?: '—' }}</td><td>{{ $row['category'] }}</td><td>{{ $row['reconciliation'] }}</td>
                <td class="money">{{ $money($row['balance']) }}</td>
            </tr>
        @empty
            <tr><td colspan="9">Nenhuma movimentação para os filtros selecionados.</td></tr>
        @endforelse
    </tbody>
</table>
<table class="totals">
    @foreach (['debits' => 'Débitos da conta', 'credits' => 'Créditos da conta', 'net' => 'Movimento líquido', 'closing' => 'Saldo final', 'available' => 'Saldo com limite'] as $key => $label)
        <tr><th>{{ $label }}</th><td class="money">R$ {{ $money($report[$key]) }}</td></tr>
    @endforeach
    @if ($report['filtered'])
        <tr><th>Débitos selecionados</th><td class="money">R$ {{ $money($report['selected_debits']) }}</td></tr>
        <tr><th>Créditos selecionados</th><td class="money">R$ {{ $money($report['selected_credits']) }}</td></tr>
    @endif
</table>
