<?php

namespace App\Filament\Portal\Widgets;

use App\Models\Client;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PortalStatsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $client = Client::where('portal_user_id', Auth::id())->first();

        if (! $client) {
            return [];
        }

        $contratosAtivos = $client->contracts()->where('status', 'ativo')->count();

        $proximaParcela = $client->receivables()
            ->whereIn('status', ['pendente', 'vencido'])
            ->orderBy('data_vencimento')
            ->first();

        $documentos = $client->clientDocuments()->where('visivel_portal', true)->count();

        $progresso = $client->nr1ChecklistProgresso();

        $nr1Label = match ($client->nr1_status) {
            'finalizada'   => 'Finalizada',
            'regularizada' => 'Regularizada',
            'em_andamento' => 'Em Andamento',
            default        => 'Pendente',
        };

        $nr1Color = match ($client->nr1_status) {
            'finalizada', 'regularizada' => 'success',
            'em_andamento'               => 'warning',
            default                      => 'danger',
        };

        $parcelaDesc = $proximaParcela
            ? $proximaParcela->data_vencimento->format('d/m/Y')
            : 'Nenhuma pendente';

        $parcelaColor = match (true) {
            $proximaParcela?->status === 'vencido' => 'danger',
            $proximaParcela !== null               => 'warning',
            default                                => 'success',
        };

        return [
            Stat::make('Contratos Ativos', $contratosAtivos)
                ->description('Contratos em vigor')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),

            Stat::make(
                'Próximo Vencimento',
                $proximaParcela ? 'R$ ' . number_format((float) $proximaParcela->valor, 2, ',', '.') : '—'
            )
                ->description($parcelaDesc)
                ->descriptionIcon('heroicon-m-calendar-days')
                ->color($parcelaColor),

            Stat::make('Documentos', $documentos)
                ->description('Disponíveis no portal')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('info'),

            Stat::make('NR-1', $nr1Label)
                ->description($progresso . '% das etapas concluídas')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($nr1Color),
        ];
    }
}
