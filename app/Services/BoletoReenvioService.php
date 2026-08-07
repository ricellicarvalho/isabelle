<?php

namespace App\Services;

use App\Models\BankBoleto;
use App\Models\BankRemessa;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use RuntimeException;

class BoletoReenvioService
{
    public const REMESSA_SEQUENCIAL = 5;

    /**
     * @return Collection<int,array{
     *     client_id:int|null,
     *     cliente:string,
     *     quantidade:int,
     *     valor_total:float,
     *     primeiro_vencimento:string|null,
     *     ultimo_vencimento:string|null
     * }>
     */
    public function gruposPorCliente(): Collection
    {
        return $this->boletos()
            ->groupBy(fn (BankBoleto $boleto): int|string => $boleto->receivable?->client_id ?? 'sem-cliente')
            ->map(function (Collection $boletos): array {
                /** @var BankBoleto $first */
                $first = $boletos->first();

                return [
                    'client_id' => $first->receivable?->client_id,
                    'cliente' => $first->receivable?->client?->razao_social ?? 'Sem cliente',
                    'quantidade' => $boletos->count(),
                    'valor_total' => (float) $boletos->sum('valor'),
                    'primeiro_vencimento' => $boletos->min(fn (BankBoleto $boleto) => $boleto->data_vencimento?->toDateString()),
                    'ultimo_vencimento' => $boletos->max(fn (BankBoleto $boleto) => $boleto->data_vencimento?->toDateString()),
                ];
            })
            ->sortBy('cliente', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();
    }

    public function renderPdfCliente(int $clientId): array
    {
        $boletos = $this->boletos()
            ->filter(fn (BankBoleto $boleto): bool => $boleto->receivable?->client_id === $clientId)
            ->values();

        if ($boletos->isEmpty()) {
            throw new RuntimeException('Nenhum boleto encontrado para este cliente.');
        }

        $this->recalcular($boletos);

        $cliente = $boletos->first()->receivable?->client?->razao_social ?? 'cliente';

        return [
            'filename' => $this->filename($cliente),
            'pdf' => BankBoletoService::renderBatchPdf($boletos),
        ];
    }

    public function renderPdfTodos(): array
    {
        $boletos = $this->boletos();

        if ($boletos->isEmpty()) {
            throw new RuntimeException('Nenhum boleto encontrado para reenvio.');
        }

        $this->recalcular($boletos);

        return [
            'filename' => 'boletos-reenvio-remessa-' . self::REMESSA_SEQUENCIAL . '.pdf',
            'pdf' => BankBoletoService::renderBatchPdf($boletos),
        ];
    }

    public function totalBoletos(): int
    {
        return $this->boletos()->count();
    }

    public function valorTotal(): float
    {
        return (float) $this->boletos()->sum('valor');
    }

    /**
     * @return EloquentCollection<int,BankBoleto>
     */
    private function boletos(): EloquentCollection
    {
        $remessaId = BankRemessa::query()
            ->where('sequencial_arquivo', self::REMESSA_SEQUENCIAL)
            ->value('id');

        if (! $remessaId) {
            return new EloquentCollection;
        }

        return BankBoleto::query()
            ->where('remessa_id', $remessaId)
            ->whereIn('status', ['pendente', 'emitido'])
            ->with(['receivable.client'])
            ->orderBy('data_vencimento')
            ->orderBy('id')
            ->get();
    }

    /**
     * @param  Collection<int,BankBoleto>|EloquentCollection<int,BankBoleto>  $boletos
     */
    private function recalcular(Collection|EloquentCollection $boletos): void
    {
        foreach ($boletos as $boleto) {
            BankBoletoService::buildLibBoleto($boleto);
        }
    }

    private function filename(string $cliente): string
    {
        $base = str($cliente)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '-')
            ->trim('-')
            ->limit(60, '')
            ->toString();

        return 'boletos-reenvio-' . ($base ?: 'cliente') . '.pdf';
    }
}
