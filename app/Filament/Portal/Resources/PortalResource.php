<?php

namespace App\Filament\Portal\Resources;

use Filament\Resources\Resource;

/**
 * Base para todos os Resources do painel Portal.
 *
 * Segurança em 3 camadas já garantida externamente:
 *   1. Autenticação   → Filament Authenticate middleware (guard 'portal')
 *   2. Autorização    → canAccessPanel() exige role 'cliente'
 *   3. Isolamento     → getEloquentQuery() filtra por portal_user_id
 *
 * Por isso dispensamos o Shield aqui: canViewAny/canView retornam true
 * para qualquer usuário autenticado no portal, e escrita é bloqueada
 * por padrão (portal é somente leitura).
 */
abstract class PortalResource extends Resource
{
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView($record): bool
    {
        return true;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
