<?php

namespace App\Jobs;

use App\Models\Nfse;
use App\Models\NfseConfig;
use App\Services\NFSeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class CancelarNFSeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public array $backoff = [60, 180];

    public function __construct(
        public readonly Nfse   $nfse,
        public readonly string $motivo,
        public readonly string $codigo,
    ) {}

    public function handle(NFSeService $service): void
    {
        $config = NfseConfig::ativa();

        if (! $config) {
            $this->fail('Nenhuma configuração NFSe ativa encontrada.');

            return;
        }

        try {
            $result = $service->cancelar($config, $this->nfse, $this->motivo, $this->codigo);
            $body   = $result['body'] ?? [];

            if (($body['situacao'] ?? '') === 'sucesso') {
                $this->nfse->update([
                    'status'             => 'cancelada',
                    'motivo_cancelamento' => $this->motivo,
                    'codigo_cancelamento' => $this->codigo,
                    'xml_cancelamento'   => $body['xml'] ?? null,
                    'ultimo_erro'        => null,
                ]);
            } else {
                throw new \RuntimeException($body['mensagem'] ?? 'Erro ao cancelar NFSe.');
            }
        } catch (Throwable $e) {
            Log::channel(config('nfse.log_channel'))->error('CancelarNFSeJob falhou', [
                'nfse_id' => $this->nfse->id,
                'erro'    => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::channel(config('nfse.log_channel'))->error('CancelarNFSeJob esgotou tentativas', [
            'nfse_id' => $this->nfse->id,
            'erro'    => $exception->getMessage(),
        ]);
    }
}
