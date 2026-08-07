<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Services\BankBoletoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalcularCodigosBoletosBradesco extends Command
{
    protected $signature = 'bank-boletos:recalcular-codigos-bradesco
                            {--ids= : Lista de IDs separada por virgula}
                            {--dry-run : Mostra o que mudaria sem gravar}
                            {--force : Executa sem pedir confirmacao}';

    protected $description = 'Recalcula codigo de barras e linha digitavel dos boletos Bradesco sem alterar nosso numero';

    public function handle(): int
    {
        $account = BankAccount::active();
        if (! $account || $account->banco !== '237') {
            $this->error('A conta bancaria ativa precisa ser Bradesco (237).');

            return self::FAILURE;
        }

        $query = BankBoleto::query()
            ->whereIn('status', ['pendente', 'emitido'])
            ->orderBy('id');

        $ids = $this->parseIds((string) $this->option('ids'));
        if ($ids !== []) {
            $query->whereIn('id', $ids);
        }

        $boletos = $query->get();
        if ($boletos->isEmpty()) {
            $this->info('Nenhum boleto elegivel encontrado.');

            return self::SUCCESS;
        }

        if (! $this->option('dry-run') && ! $this->option('force')
            && ! $this->confirm("Confirma o recálculo de {$boletos->count()} boleto(s) elegível(is)?", true)) {
            $this->info('Operacao cancelada.');

            return self::SUCCESS;
        }

        $changes = [];
        foreach ($boletos as $boleto) {
            $oldCodigo = $boleto->codigo_barras;
            $oldLinha = $boleto->linha_digitavel;

            if ($this->option('dry-run')) {
                DB::beginTransaction();
                try {
                    BankBoletoService::buildLibBoleto($boleto);
                    $boleto->refresh();
                    $newCodigo = $boleto->codigo_barras;
                    $newLinha = $boleto->linha_digitavel;
                } finally {
                    DB::rollBack();
                    $boleto->refresh();
                }
            } else {
                BankBoletoService::buildLibBoleto($boleto);
                $boleto->refresh();
                $newCodigo = $boleto->codigo_barras;
                $newLinha = $boleto->linha_digitavel;
            }

            if ($oldCodigo !== $newCodigo || $oldLinha !== $newLinha) {
                $changes[] = [
                    $boleto->id,
                    $boleto->nosso_numero,
                    $oldLinha ?: '-',
                    $newLinha ?: '-',
                ];
            }
        }

        if ($changes === []) {
            $this->info('Todos os boletos elegiveis ja estao com codigo de barras e linha digitavel atualizados.');

            return self::SUCCESS;
        }

        $this->table(['ID', 'Nosso numero', 'Linha anterior', 'Linha nova'], $changes);

        if ($this->option('dry-run')) {
            $this->warn('Modo --dry-run: nenhuma alteracao foi mantida.');

            return self::SUCCESS;
        }

        $this->info(count($changes).' boleto(s) atualizado(s).');

        return self::SUCCESS;
    }

    /**
     * @return array<int,int>
     */
    private function parseIds(string $value): array
    {
        if (trim($value) === '') {
            return [];
        }

        return collect(explode(',', $value))
            ->map(fn (string $item): int => (int) trim($item))
            ->filter(fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
