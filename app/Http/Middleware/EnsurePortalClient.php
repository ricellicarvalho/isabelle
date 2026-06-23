<?php

namespace App\Http\Middleware;

use App\Support\PortalAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalClient
{
    /**
     * Garante que o usuário autenticado tem um Client vinculado (via portal_user_id
     * ou portal_financeiro_user_id). Caso contrário, desloga e redireciona com erro.
     * Também redireciona o escopo financeiro para fora do Dashboard, já que ele
     * tem acesso restrito a Boletos e Notas Fiscais.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $guard = Auth::guard('portal');
        $user = $guard->user();

        if (! $user) {
            return $next($request);
        }

        $client = PortalAccess::client($user->id);

        if (! $client) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.portal.auth.login')
                ->withErrors(['email' => 'Seu usuário não está vinculado a nenhuma empresa cliente.']);
        }

        $scope = PortalAccess::scope($user->id);

        // Disponibiliza Client e escopo no request para uso nas páginas do portal
        $request->merge(['_portal_client' => $client, '_portal_scope' => $scope]);

        if ($scope === PortalAccess::SCOPE_FINANCEIRO && $request->routeIs('filament.portal.pages.dashboard')) {
            return redirect()->route('filament.portal.resources.bank-boletos.index');
        }

        return $next($request);
    }
}
