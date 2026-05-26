<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractFinalizedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contract $contract,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (config('mail.default') && filled(config('mail.from.address'))) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contract = $this->contract->loadMissing('client');
        $cliente  = $contract->client?->razao_social ?? '—';
        $valor    = number_format((float) $contract->valor_total, 2, ',', '.');
        $dataFim  = optional($contract->data_fim)->format('d/m/Y');
        $url      = url("/admin/contracts/{$contract->getKey()}/edit");

        return (new MailMessage)
            ->subject("Contrato {$contract->numero} foi finalizado hoje ({$dataFim})")
            ->greeting("Olá, {$notifiable->name}")
            ->line("O contrato **{$contract->numero}** do cliente **{$cliente}** atingiu sua data de encerramento hoje, **{$dataFim}**, e foi **automaticamente finalizado** pelo sistema.")
            ->line("**Valor total do contrato:** R$ {$valor}")
            ->line("**Status atual:** Finalizado")
            ->action('Abrir contrato', $url)
            ->line('Verifique a necessidade de renovação ou emissão de novo contrato para este cliente.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $contract = $this->contract->loadMissing('client');

        return [
            'contract_id' => $contract->getKey(),
            'numero'      => $contract->numero,
            'client_id'   => $contract->client_id,
            'cliente'     => $contract->client?->razao_social,
            'valor_total' => (float) $contract->valor_total,
            'data_fim'    => optional($contract->data_fim)->toDateString(),
            'title'       => "Contrato {$contract->numero} finalizado em " . optional($contract->data_fim)->format('d/m/Y'),
            'url'         => "/admin/contracts/{$contract->getKey()}/edit",
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
