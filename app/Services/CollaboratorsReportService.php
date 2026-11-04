<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Colaborador;

class CollaboratorsReportService
{
    public static function generate(array $filters): array
    {
        $clientId = filled($filters['client_id'] ?? null) ? (int) $filters['client_id'] : null;
        $search = trim((string) ($filters['search'] ?? ''));

        $collaborators = Colaborador::query()
            ->with('client:id,razao_social')
            ->when($clientId, fn ($query, int $id) => $query->where('client_id', $id))
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('nome', 'like', "%{$search}%")
                        ->orWhere('telefone', 'like', "%{$search}%")
                        ->orWhere('local', 'like', "%{$search}%")
                        ->orWhereHas('client', fn ($query) => $query->where('razao_social', 'like', "%{$search}%"));
                });
            })
            ->orderBy(Client::query()
                ->select('razao_social')
                ->whereColumn('clients.id', 'colaboradores.client_id'))
            ->orderBy('nome')
            ->orderBy('id')
            ->get();

        return [
            'items' => $collaborators->map(fn (Colaborador $collaborator): array => [
                'id' => $collaborator->id,
                'client_id' => $collaborator->client_id,
                'cliente' => $collaborator->client?->razao_social ?? '—',
                'nome' => $collaborator->nome,
                'telefone' => $collaborator->telefone,
                'local' => $collaborator->local,
                'cadastrado_em' => $collaborator->created_at?->format('d/m/Y H:i'),
            ])->all(),
            'count' => $collaborators->count(),
            'clients_count' => $collaborators->pluck('client_id')->unique()->count(),
            'filters' => [
                'client_id' => $clientId,
                'cliente' => $clientId ? Client::find($clientId)?->razao_social : null,
                'search' => $search,
            ],
        ];
    }
}
