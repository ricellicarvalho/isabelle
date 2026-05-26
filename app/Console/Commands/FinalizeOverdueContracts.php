<?php

namespace App\Console\Commands;

use App\Models\Contract;
use App\Models\NotificationLog;
use App\Models\User;
use App\Notifications\ContractFinalizedNotification;
use Filament\Actions\Action as FilamentAction;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

class FinalizeOverdueContracts extends Command
{
    protected $signature = 'contracts:finalize-overdue
                            {--dry-run : Apenas lista os contratos sem alterar nada}';

    protected $description = 'Finaliza contratos com data_fim no passado que ainda estão como Ativo e envia notificações';

    public function handle(): int
    {
        $today  = Carbon::today();
        $isDry  = $this->option('dry-run');

        $contratos = Contract::query()
            ->where('status', 'ativo')
            ->whereDate('data_fim', '<', $today->toDateString())
            ->with('client')
            ->orderBy('data_fim')
            ->get();

        if ($contratos->isEmpty()) {
            $this->info('Nenhum contrato vencido encontrado com status Ativo.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Número', 'Cliente', 'Data Fim', 'Dias em atraso'],
            $contratos->map(fn (Contract $c) => [
                $c->id,
                $c->numero,
                $c->client?->razao_social ?? '—',
                $c->data_fim?->format('d/m/Y'),
                $today->diffInDays($c->data_fim) . ' dias',
            ])->toArray()
        );

        if ($isDry) {
            $this->warn('Modo --dry-run: nenhuma alteração foi feita.');
            return self::SUCCESS;
        }

        if (! $this->confirm("Confirma a finalização de {$contratos->count()} contrato(s) e envio de notificações?", true)) {
            $this->info('Operação cancelada.');
            return self::SUCCESS;
        }

        $admins = User::role(['super_admin', 'administrador'])->get();

        if ($admins->isEmpty()) {
            $this->warn('Nenhum administrador encontrado — status será atualizado sem notificação.');
        }

        $total = 0;

        foreach ($contratos as $contract) {
            $contract->update(['status' => 'finalizado']);

            if ($admins->isNotEmpty()) {
                FilamentNotification::make()
                    ->title("Contrato {$contract->numero} finalizado")
                    ->body('Cliente: ' . ($contract->client?->razao_social ?? '—') . ' — encerrado em ' . optional($contract->data_fim)->format('d/m/Y'))
                    ->success()
                    ->actions([
                        FilamentAction::make('ver')
                            ->label('Abrir contrato')
                            ->url(\App\Filament\Resources\Contracts\ContractResource::getUrl('edit', ['record' => $contract->getKey()])),
                    ])
                    ->sendToDatabase($admins);

                Notification::send($admins, new ContractFinalizedNotification($contract));

                NotificationLog::create([
                    'notifiable_type' => Contract::class,
                    'notifiable_id'   => $contract->getKey(),
                    'alert_type'      => 'contract_finalized',
                    'days_before'     => 0,
                    'sent_date'       => $today->toDateString(),
                ]);
            }

            $total++;
            $this->line("✓ {$contract->numero} — {$contract->client?->razao_social}");
        }

        $this->info("Concluído: {$total} contrato(s) finalizado(s).");

        return self::SUCCESS;
    }
}
