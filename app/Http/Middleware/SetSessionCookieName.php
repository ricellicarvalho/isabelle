<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sobrescreve o nome do cookie de sessão antes do StartSession.
 *
 * Garante isolamento das sessões dos painéis Admin e Portal mesmo quando
 * acessados no mesmo navegador (em ambientes locais com mesmo host) e oferece
 * defesa em profundidade em produção (subdomínios já isolam por si só).
 *
 * Uso (no array de middleware do PanelProvider, ANTES de StartSession):
 *   \App\Http\Middleware\SetSessionCookieName::class.':isabelle_admin_session',
 */
class SetSessionCookieName
{
    public function handle(Request $request, Closure $next, string $cookieName): Response
    {
        config(['session.cookie' => $cookieName]);

        return $next($request);
    }
}
