<?php

namespace App\Notifications;

use App\Models\Contract;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContractExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Contract $contract,
        public int $daysRemaining,
    ) {
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if (config('mail.default') && filled(config('mail.from.address'))) {
            return ['mail'];
        }

        return [];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $contract = $this->contract->loadMissing('client');
        $cliente  = $contract->client?->razao_social ?? '—';
        $valor    = number_format((float) $contract->valor_total, 2, ',', '.');
        $dataFim  = optional($contract->data_fim)->format('d/m/Y');
        $url      = \App\Filament\Resources\Contracts\ContractResource::getUrl('edit', ['record' => $contract->getKey()]);

        return (new MailMessage)
            ->subject("Contrato {$contract->numero} vence em {$this->daysRemaining} dias")
            ->greeting("Olá, {$notifiable->name}")
            ->line("O contrato **{$contract->numero}** ({$cliente}) vence em **{$this->daysRemaining} dias** ({$dataFim}).")
            ->line("Valor total: R$ {$valor}")
            ->action('Abrir contrato', $url)
            ->line('Verifique a necessidade de renovação ou comunicação com o cliente.');
    }
}
