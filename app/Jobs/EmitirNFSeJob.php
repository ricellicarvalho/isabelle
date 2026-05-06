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

class EmitirNFSeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    public function __construct(public readonly Nfse $nfse) {}

    public function handle(NFSeService $service): void
    {
        $config = NfseConfig::ativa();

        if (! $config) {
            $this->nfse->update([
                'status'     => 'erro',
                'ultimo_erro' => 'Nenhuma configuração NFSe ativa encontrada.',
            ]);

            $this->fail('Nenhuma configuração NFSe ativa encontrada.');

            return;
        }

        $client = $this->nfse->receivable?->client ?? $this->nfse->contract?->client;

        if (! $client) {
            $this->nfse->update([
                'status'     => 'erro',
                'ultimo_erro' => 'Cliente não encontrado para emissão da NFSe.',
            ]);

            $this->fail('Cliente não encontrado.');

            return;
        }

        $this->nfse->update(['status' => 'processando', 'tentativas' => $this->nfse->tentativas + 1]);

        try {
            $result = $service->emitir($config, $this->nfse, $client);
            $body   = $result['body'] ?? [];

            if (($body['situacao'] ?? '') === 'sucesso') {
                $this->nfse->update([
                    'status'            => 'emitida',
                    'numero'            => $body['numero'] ?? null,
                    'chave_dfe'         => $body['chave'] ?? $body['chaveDfe'] ?? null,
                    'codigo_verificacao' => $body['codigoVerificacao'] ?? null,
                    'xml'               => $body['xml'] ?? null,
                    'pdf'               => $body['pdf'] ?? null,
                    'data_emissao'      => now(),
                    'ultimo_erro'       => null,
                ]);

                EnviarEmailNFSeJob::dispatch($this->nfse->fresh());
            } else {
                $erro = $body['mensagem'] ?? 'Erro desconhecido retornado pela API.';

                $this->nfse->update([
                    'status'     => 'erro',
                    'ultimo_erro' => $erro,
                ]);

                throw new \RuntimeException($erro);
            }
        } catch (Throwable $e) {
            Log::channel(config('nfse.log_channel'))->error('EmitirNFSeJob falhou', [
                'nfse_id' => $this->nfse->id,
                'erro'    => $e->getMessage(),
                'tentativa' => $this->nfse->tentativas,
            ]);

            if ($this->attempts() >= $this->tries) {
                $this->nfse->update(['status' => 'erro', 'ultimo_erro' => $e->getMessage()]);
            }

            throw $e;
        }
    }

    public function failed(Throwable $exception): void
    {
        $this->nfse->update([
            'status'     => 'erro',
            'ultimo_erro' => $exception->getMessage(),
        ]);
    }
}
