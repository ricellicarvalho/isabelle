<?php

namespace App\Mail;

use App\Models\Nfse;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NFSeEmitidaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Nfse $nfse) {}

    public function envelope(): Envelope
    {
        $numero = $nfse->numero ?? "RPS-{$this->nfse->numero_rps}";

        return new Envelope(
            subject: "Nota Fiscal de Serviço Eletrônica #{$numero} — " . config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.nfse_emitida',
            with: [
                'nfse'     => $this->nfse,
                'client'   => $this->nfse->receivable?->client ?? $this->nfse->contract?->client,
                'contract' => $this->nfse->contract,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if ($this->nfse->pdf) {
            $numero = $this->nfse->numero ?? "RPS-{$this->nfse->numero_rps}";
            $attachments[] = Attachment::fromData(
                fn () => hex2bin($this->nfse->pdf),
                "NFSe-{$numero}.pdf"
            )->withMime('application/pdf');
        }

        if ($this->nfse->xml) {
            $numero = $this->nfse->numero ?? "RPS-{$this->nfse->numero_rps}";
            $attachments[] = Attachment::fromData(
                fn () => hex2bin($this->nfse->xml),
                "NFSe-{$numero}.xml"
            )->withMime('application/xml');
        }

        return $attachments;
    }
}
