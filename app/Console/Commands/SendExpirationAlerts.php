<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\ContractExpiringNotification;
use App\Notifications\ContractFinalizedNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class SendExpirationAlerts extends Command
{
    protected $signature = 'alerts:expiring';

    protected $description = 'Alertas preventivos (30/15/7 dias) e finalização automática de contratos vencidos (RN08)';

    /**
     * Dias de antecedência para alertas preventivos.
     *
     * @var array<int, int>
     */
    protected array $thresholds = [30, 15, 7];

    public function handle(): int
    {
        $today  = Carbon::today();
        $admins = $this->admins();

        if ($admins->isEmpty()) {
            $this->warn('Nenhum usuário administrador encontrado. Nenhuma notificação enviada.');

            return self::SUCCESS;
        }

        $totalAlerts    = 0;
        $totalFinalized = 0;

        // --- Alertas preventivos: 30, 15 e 7 dias antes do vencimento ---
        foreach ($this->thresholds as $days) {
            $targetDate = $today->copy()->addDays($days)->toDateString();

            $contracts = Contract::query()
                ->where('status', 'ativo')
                ->whereDate('data_fim', $targetDate)
                ->with('client')
                ->get();

            foreach ($contracts as $contract) {
                $alreadySent = NotificationLog::query()
                    ->where('notifiable_type', Contract::class)
                    ->where('notifiable_id', $contract->getKey())
                    ->where('alert_type', 'contract_expiring')
                    ->where('days_before', $days)
                    ->where('sent_date', $today->toDateString())
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                Notification::send($admins, new ContractExpiringNotification($contract, $days));

                NotificationLog::create([
                    'notifiable_type' => Contract::class,
                    'notifiable_id'   => $contract->getKey(),
                    'alert_type'      => 'contract_expiring',
                    'days_before'     => $days,
                    'sent_date'       => $today->toDateString(),
                ]);

                $totalAlerts++;

                $this->line("✓ Alerta ({$days}d): Contrato {$contract->numero} — {$contract->client?->razao_social}");
            }
        }

        // --- Finalização automática: contratos que vencem hoje ---
        $expiredContracts = Contract::query()
            ->where('status', 'ativo')
            ->whereDate('data_fim', $today->toDateString())
            ->with('client')
            ->get();

        foreach ($expiredContracts as $contract) {
            $alreadyProcessed = NotificationLog::query()
                ->where('notifiable_type', Contract::class)
                ->where('notifiable_id', $contract->getKey())
                ->where('alert_type', 'contract_finalized')
                ->where('sent_date', $today->toDateString())
                ->exists();

            if ($alreadyProcessed) {
                continue;
            }

            $contract->update(['status' => 'finalizado']);

            Notification::send($admins, new ContractFinalizedNotification($contract));

            NotificationLog::create([
                'notifiable_type' => Contract::class,
                'notifiable_id'   => $contract->getKey(),
                'alert_type'      => 'contract_finalized',
                'days_before'     => 0,
                'sent_date'       => $today->toDateString(),
            ]);

            $totalFinalized++;

            $this->line("✓ Finalizado: Contrato {$contract->numero} — {$contract->client?->razao_social}");
        }

        $this->info("Alertas preventivos: {$totalAlerts} | Contratos finalizados: {$totalFinalized}");

        return self::SUCCESS;
    }

    /**
     * Retorna usuários com role de administrador (super_admin ou administrador).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, User>
     */
    private function admins(): \Illuminate\Database\Eloquent\Collection
    {
        return User::role(['super_admin', 'administrador'])->get();
    }
}
