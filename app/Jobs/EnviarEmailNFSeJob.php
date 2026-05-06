<?php

namespace App\Jobs;

use App\Mail\NFSeEmitidaMail;
use App\Models\Nfse;
use App\Models\NfseConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class EnviarEmailNFSeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 120, 300];

    public function __construct(public readonly Nfse $nfse) {}

    public function handle(): void
    {
        if (! $this->nfse->pdf) {
            Log::warning('EnviarEmailNFSeJob: NFSe sem PDF', ['nfse_id' => $this->nfse->id]);

            return;
        }

        $config = NfseConfig::ativa();

        $destinatarios = array_filter([
            $config?->email,
            $this->nfse->receivable?->client?->email ?? $this->nfse->contract?->client?->email,
        ]);

        foreach ($destinatarios as $email) {
            try {
                Mail::to($email)->send(new NFSeEmitidaMail($this->nfse));
            } catch (Throwable $e) {
                Log::channel(config('nfse.log_channel'))->error('Falha ao enviar e-mail NFSe', [
                    'nfse_id' => $this->nfse->id,
                    'email'   => $email,
                    'erro'    => $e->getMessage(),
                ]);
            }
        }
    }
}
