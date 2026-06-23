<?php

namespace App\Support;

use App\Models\Client;

/**
 * Resolve qual Client e qual escopo de acesso pertencem a um usuário
 * autenticado no painel 'portal'. Um Client pode ter até 2 credenciais:
 * portal_user_id (documentação) e portal_financeiro_user_id (financeiro).
 */
class PortalAccess
{
    public const SCOPE_DOCUMENTACAO = 'documentacao';

    public const SCOPE_FINANCEIRO = 'financeiro';

    public static function client(int $userId): ?Client
    {
        return Client::where('portal_user_id', $userId)
            ->orWhere('portal_financeiro_user_id', $userId)
            ->first();
    }

    public static function scope(int $userId): ?string
    {
        $client = static::client($userId);

        if (! $client) {
            return null;
        }

        return $client->portal_financeiro_user_id === $userId
            ? self::SCOPE_FINANCEIRO
            : self::SCOPE_DOCUMENTACAO;
    }
}
