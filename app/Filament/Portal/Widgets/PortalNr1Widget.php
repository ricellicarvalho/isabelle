<?php

namespace App\Filament\Portal\Widgets;

use App\Support\PortalAccess;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class PortalNr1Widget extends Widget
{
    protected string $view = 'filament.portal.widgets.portal-nr1-widget';

    protected int|string|array $columnSpan = 'full';

    public ?int $clientId = null;

    public array $checklist = [];

    public int $progresso = 0;

    public string $nr1Status = 'pendente';

    public string $nr1Label = 'Pendente';

    public string $clientName = '';

    public ?int $referenceYear = null;

    public ?string $contractNumber = null;

    public array $history = [];

    public array $devolutivas = [];

    public function mount(): void
    {
        $client = PortalAccess::client(Auth::id());

        if (! $client) {
            return;
        }

        $this->clientId = $client->id;
        $cycles = $client->nr1Cycles()
            ->with(['contract', 'devolutivas' => fn ($query) => $query
                ->where('visivel_portal', true)
                ->latest()])
            ->get();
        $current = $cycles->first();
        $this->checklist = $current?->checklist ?? [];
        $this->progresso = $current?->checklistProgresso() ?? 0;
        $this->nr1Status = $current?->status ?? 'pendente';
        $this->referenceYear = $current?->reference_year;
        $this->contractNumber = $current?->contract?->numero;
        $this->devolutivas = $this->formatDevolutivas($current?->devolutivas ?? collect());
        $this->history = $cycles->map(fn ($cycle): array => [
            'year' => $cycle->reference_year,
            'status' => $cycle->status,
            'progress' => $cycle->checklistProgresso(),
            'contract' => $cycle->contract?->numero,
            'checklist' => $cycle->checklist ?? [],
            'devolutivas' => $this->formatDevolutivas($cycle->devolutivas),
        ])->all();
        $this->clientName = $client->nome_fantasia ?: $client->razao_social;

        $this->nr1Label = match ($this->nr1Status) {
            'finalizada' => 'Finalizada',
            'regularizada' => 'Regularizada',
            'em_andamento' => 'Em Andamento',
            default => 'Pendente',
        };
    }

    private function formatDevolutivas($documents): array
    {
        return $documents->map(fn ($document): array => [
            'id' => $document->id,
            'title' => $document->titulo,
            'description' => $document->descricao,
            'files' => collect((array) $document->caminho_arquivo)
                ->values()
                ->map(fn (string $path, int $index): array => [
                    'name' => basename($path),
                    'view_url' => route('portal.document.file', [$document->id, $index]),
                    'download_url' => route('portal.document.file', [$document->id, $index, 'download' => 1]),
                ])->all(),
        ])->all();
    }
}
