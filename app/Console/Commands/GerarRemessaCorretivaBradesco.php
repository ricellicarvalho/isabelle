<?php

namespace App\Console\Commands;

use App\Models\BankAccount;
use App\Models\BankBoleto;
use App\Models\BankRemessa;
use App\Services\CnabRemessaService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class GerarRemessaCorretivaBradesco extends Command
{
    protected $signature = 'bank-remessas:gerar-corretiva-bradesco
                            {--sequencial=5 : Sequencial da nova remessa corretiva}
                            {--remessas=1,2,3,4 : Sequenciais das remessas rejeitadas}
                            {--expected-count=47 : Quantidade esperada de boletos consolidados}
                            {--dry-run : Valida e mostra o resumo sem gravar dados nem gerar arquivo}
                            {--force : Executa sem pedir confirmação}';

    protected $description = 'Gera uma remessa Bradesco corretiva consolidando boletos de remessas rejeitadas';

    public function handle(): int
    {
        $sequencial = (int) $this->option('sequencial');
        $sourceSequenciais = $this->parseSequenciais((string) $this->option('remessas'));
        $expectedCount = (int) $this->option('expected-count');

        if ($sequencial < 1 || $expectedCount < 1 || $sourceSequenciais === []) {
            $this->error('Informe sequencial, remessas e quantidade esperada válidos.');

            return self::FAILURE;
        }

        if (in_array($sequencial, $sourceSequenciais, true)) {
            $this->error('O sequencial corretivo não pode estar entre as remessas de origem.');

            return self::FAILURE;
        }

        $existing = BankRemessa::query()->where('sequencial_arquivo', $sequencial)->first();
        if ($existing) {
            $this->warn("A remessa sequencial {$sequencial} já existe. Nenhuma alteração foi feita.");
            $this->line("ID: {$existing->id}");
            $this->line("Arquivo: {$existing->caminho_arquivo}");

            return self::SUCCESS;
        }

        $account = BankAccount::active();
        if (! $account || $account->banco !== '237') {
            $this->error('A conta bancária ativa precisa ser Bradesco (237).');

            return self::FAILURE;
        }

        $remessas = BankRemessa::query()
            ->whereIn('sequencial_arquivo', $sourceSequenciais)
            ->with('boletos')
            ->get();

        $foundSequenciais = $remessas->pluck('sequencial_arquivo')->all();
        $missing = array_values(array_diff($sourceSequenciais, $foundSequenciais));
        if ($missing !== []) {
            $this->error('Remessas de origem não encontradas: ' . implode(', ', $missing));

            return self::FAILURE;
        }

        $boletoIds = $remessas
            ->flatMap(fn (BankRemessa $remessa) => $remessa->boletos->pluck('id'))
            ->unique()
            ->values();

        if ($boletoIds->count() !== $expectedCount) {
            $this->error("Foram encontrados {$boletoIds->count()} boletos únicos; esperado: {$expectedCount}.");

            return self::FAILURE;
        }

        $boletos = BankBoleto::query()
            ->whereIn('id', $boletoIds)
            ->with(['receivable.client'])
            ->orderBy('data_vencimento')
            ->orderBy('id')
            ->get();

        $this->table(['Item', 'Valor'], [
            ['Nova remessa', $sequencial],
            ['Remessas de origem', implode(', ', $sourceSequenciais)],
            ['Boletos', $boletos->count()],
            ['Valor total', 'R$ ' . number_format((float) $boletos->sum('valor'), 2, ',', '.')],
            ['Arquivo', CnabRemessaService::filename($sequencial)],
        ]);

        if ($this->option('dry-run')) {
            $this->warn('Modo --dry-run: nenhuma alteração foi feita.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Confirma a geração da remessa corretiva?', true)) {
            $this->info('Operação cancelada. Nenhuma alteração foi feita.');

            return self::SUCCESS;
        }

        /** @var EloquentCollection<int,BankBoleto> $boletos */
        $remessa = CnabRemessaService::generateCorrective($boletos, $sequencial);

        $this->info("Remessa corretiva {$remessa->sequencial_arquivo} gerada com sucesso.");
        $this->line("ID: {$remessa->id}");
        $this->line("Arquivo: {$remessa->caminho_arquivo}");
        $this->line("Títulos: {$remessa->quantidade_titulos}");

        return self::SUCCESS;
    }

    /**
     * @return array<int,int>
     */
    private function parseSequenciais(string $value): array
    {
        return collect(explode(',', $value))
            ->map(fn (string $item): int => (int) trim($item))
            ->filter(fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
