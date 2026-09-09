<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;

/** Explicit strings keep user-entered references from becoming spreadsheet formulas. */
class BankAccountReportExport extends StringValueBinder implements FromArray, WithCustomValueBinder, WithTitle
{
    public function __construct(private array $report) {}

    public function bindValue(\PhpOffice\PhpSpreadsheet\Cell\Cell $cell, $value)
    {
        $row = $cell->getRow();
        $column = $cell->getColumn();
        $lastMovement = 6 + count($this->report['rows']);
        $numeric = ($row >= 7 && $row <= $lastMovement && in_array($column, ['C', 'D', 'I'], true))
            || ($row >= $lastMovement + 2 && $row <= $lastMovement + 9 && $column === 'B')
            || ($row === 3 && $column === 'E');
        if ($numeric && is_numeric($value)) {
            $cell->setValueExplicit($value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $cell->getStyle()->getNumberFormat()->setFormatCode('#,##0.00');

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function title(): string
    {
        return 'Movimentação de Conta';
    }

    public function array(): array
    {
        $r = $this->report;
        $rows = [
            [$r['company'], 'Movimentação de Conta'],
            ['Conta', $r['account']['name'], 'Agência', $r['account']['agency'], 'Número', $r['account']['number']],
            ['Período', $r['period']['start'], $r['period']['end'], 'Limite', $r['account']['credit_limit']],
            ['Categoria', $r['filters']['category'] ?? 'Todas', 'Conciliação', $r['filters']['reconciliation'] ?? 'Todas', 'Busca', $r['filters']['search'] ?? ''],
            [''],
            ['Lançamento', 'Documento/Referência', 'Débito', 'Crédito', 'Histórico', 'Favorecido/Cliente', 'Categoria', 'Conciliação', 'Saldo da conta'],
        ];
        foreach ($r['rows'] as $row) {
            $rows[] = [$row['data'], $row['reference'], $row['debit'], $row['credit'], $row['description'], $row['counterparty'], $row['category'], $row['reconciliation'], $row['balance']];
        }
        $rows[] = [''];
        foreach (['opening' => 'Saldo anterior', 'debits' => 'Débitos da conta', 'credits' => 'Créditos da conta', 'net' => 'Movimento líquido', 'closing' => 'Saldo final', 'available' => 'Saldo com limite', 'selected_debits' => 'Débitos selecionados', 'selected_credits' => 'Créditos selecionados'] as $key => $label) {
            $rows[] = [$label, $r[$key]];
        }
        if ($r['filtered']) {
            $rows[] = ['Filtros selecionam linhas; saldos e totais da conta incluem todos os movimentos confirmados do período.'];
        }
        if ($r['unclassified']) {
            $rows[] = [$r['unclassified'].' título(s) pago(s) desta conta ainda sem baixa bancária e fora dos totais.'];
        }

        return $rows;
    }
}
