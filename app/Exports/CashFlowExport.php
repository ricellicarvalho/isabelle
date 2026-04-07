<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CashFlowExport implements FromArray, WithHeadings, WithTitle
{
    public function __construct(protected array $report) {}

    public function headings(): array
    {
        return ['Data', 'Descrição', 'Categoria', 'Tipo', 'Valor', 'Saldo Acumulado'];
    }

    public function array(): array
    {
        $rows = [];
        foreach ($this->report['linhas'] as $linha) {
            $rows[] = [
                $linha['data']?->format('d/m/Y'),
                $linha['descricao'],
                $linha['categoria'],
                $linha['tipo'] === 'entrada' ? 'Entrada' : 'Saída',
                $linha['tipo'] === 'entrada' ? $linha['valor'] : -$linha['valor'],
                $linha['saldo_acumulado'],
            ];
        }

        $rows[] = [];
        $rows[] = ['', '', '', 'Saldo Inicial', '', $this->report['saldo_inicial']];
        $rows[] = ['', '', '', 'Total Entradas', '', $this->report['totais']['entradas']];
        $rows[] = ['', '', '', 'Total Saídas', '', $this->report['totais']['saidas']];
        $rows[] = ['', '', '', 'Saldo Final', '', $this->report['totais']['saldo_final']];

        return $rows;
    }

    public function title(): string
    {
        return 'Fluxo de Caixa';
    }
}
