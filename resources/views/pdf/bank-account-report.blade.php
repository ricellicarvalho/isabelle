<!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><title>Movimentação de Conta</title>
<style>
@page { margin: 14mm 10mm; }
body { font: 9px DejaVu Sans, sans-serif; color: #222; }
h1 { font-size: 18px; color: #1e4a5c; }
h2 { font-size: 12px; }
table { border-collapse: collapse; }
.movements { width: 100%; table-layout: fixed; }
th, td { padding: 5px 3px; border-bottom: 1px solid #ddd; text-align: left; overflow-wrap: break-word; }
.movements thead th { background: #1e4a5c; color: #fff; font-size: 8px; }
thead { display: table-header-group; }
tr { page-break-inside: avoid; }
.money { text-align: right; }
.totals { width: 40%; margin: 12px 0 0 auto; }
.report-note { padding: 7px; background: #fff3cd; }
.opening { font-size: 11px; }
</style></head><body>
<h1>Movimentação de Conta</h1>
@include('reports.bank-account-content')
<p>Emitido em {{ now()->format('d/m/Y H:i') }}</p>
</body></html>
