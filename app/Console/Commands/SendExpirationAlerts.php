<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SendExpirationAlerts extends Command
{
    protected $signature = 'alerts:expiring';

    protected $description = 'Envia alertas (30/15/7 dias) de vencimento de contratos para administradores (RN08)';

    /**
     * @var array<int, int>
     */
    protected array $thresholds = [30, 15, 7];

    public function handle(): int
    {
        $today = Carbon::today();

        $admins = User::where('is_admin', true)->get();

        if ($admins->isEmpty()) {
            $this->warn('Nenhum usuário admin encontrado. Nenhuma notificação enviada.');

            return self::SUCCESS;
        }

        $totalSent = 0;

        foreach ($this->thresholds as $days) {
            $targetDate = $today->copy()->addDays($days)->toDateString();

            $contracts = Contract::query()
                ->where('status', 'ativo')
                ->whereDate('data_fim', $targetDate)
                ->with('client')
                ->get();

            foreach ($contracts as $contract) {
                $log = NotificationLog::query()
                    ->where('notifiable_type', Contract::class)
                    ->where('notifiable_id', $contract->getKey())
                    ->where('alert_type', 'contract_expiring')
                    ->where('days_before', $days)
                    ->where('sent_date', $today->toDateString())
                    ->exists();

                if ($log) {
                    continue;
                }

                Notification::send($admins, new ContractExpiringNotification($contract, $days));

                NotificationLog::create([
                    'notifiable_type' => Contract::class,
                    'notifiable_id' => $contract->getKey(),
                    'alert_type' => 'contract_expiring',
                    'days_before' => $days,
                    'sent_date' => $today->toDateString(),
                ]);

                $totalSent++;

                $this->line("✓ Contrato {$contract->numero} (vence em {$days} dias) — notificado.");
            }
        }

        $this->info("Total de notificações enviadas: {$totalSent}");

        return self::SUCCESS;
    }
}
