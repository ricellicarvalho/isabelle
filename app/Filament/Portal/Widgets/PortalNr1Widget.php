<?php

namespace App\Filament\Portal\Widgets;

use App\Models\Client;
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

    public function mount(): void
    {
        $client = Client::where('portal_user_id', Auth::id())->first();

        if (! $client) {
            return;
        }

        $this->clientId  = $client->id;
        $this->checklist = $client->nr1_checklist ?? [];
        $this->progresso = $client->nr1ChecklistProgresso();
        $this->nr1Status = $client->nr1_status ?? 'pendente';
        $this->clientName = $client->nome_fantasia ?: $client->razao_social;

        $this->nr1Label = match ($this->nr1Status) {
            'finalizada'   => 'Finalizada',
            'regularizada' => 'Regularizada',
            'em_andamento' => 'Em Andamento',
            default        => 'Pendente',
        };
    }
}
