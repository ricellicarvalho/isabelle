<?php

namespace App\Support;

use App\Models\Client;
use Illuminate\Database\Eloquent\Collection;

/**
 * Resolve qual Client e qual escopo de acesso pertencem a um usuário
 * autenticado no painel 'portal'. Um Client pode ter até 2 credenciais:
 * portal_user_id (documentação) e portal_financeiro_user_id (financeiro).
 */
class PortalAccess
{
    public const SCOPE_DOCUMENTACAO = 'documentacao';

    public const SCOPE_FINANCEIRO = 'financeiro';

    public const SESSION_CLIENT_ID = 'portal_client_id';

    public static function client(int $userId): ?Client
    {
        return static::selectedClient($userId);
    }

    /**
     * @return Collection<int, Client>
     */
    public static function clients(int $userId): Collection
    {
        return Client::where('portal_user_id', $userId)
            ->orWhere('portal_financeiro_user_id', $userId)
            ->orderBy('nome_fantasia')
            ->orderBy('razao_social')
            ->get();
    }

    public static function selectedClient(int $userId): ?Client
    {
        $clients = static::clients($userId);

        if ($clients->isEmpty()) {
            session()->forget(self::SESSION_CLIENT_ID);

            return null;
        }

        $selectedClientId = session(self::SESSION_CLIENT_ID);
        $selectedClient = $selectedClientId
            ? $clients->firstWhere('id', (int) $selectedClientId)
            : null;

        if ($selectedClient) {
            return $selectedClient;
        }

        $selectedClient = $clients->first();
        session([self::SESSION_CLIENT_ID => $selectedClient->id]);

        return $selectedClient;
    }

    public static function selectClient(int $userId, int $clientId): bool
    {
        $clientExists = static::clients($userId)->contains('id', $clientId);

        if (! $clientExists) {
            return false;
        }

        session([self::SESSION_CLIENT_ID => $clientId]);

        return true;
    }

    public static function scope(int $userId): ?string
    {
        $client = static::client($userId);

        if (! $client) {
            return null;
        }

        if ($client->portal_user_id === $userId) {
            return self::SCOPE_DOCUMENTACAO;
        }

        return $client->portal_financeiro_user_id === $userId
            ? self::SCOPE_FINANCEIRO
            : null;
    }

    public static function userHasMultipleClients(int $userId): bool
    {
        return static::clients($userId)->count() > 1;
    }
}
