<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalClient
{
    /**
     * Garante que o usuário autenticado tem um Client vinculado via portal_user_id.
     * Caso contrário, desloga e redireciona com mensagem de erro.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (! $user) {
            return $next($request);
        }

        $client = Client::where('portal_user_id', $user->id)->first();

        if (! $client) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('filament.portal.auth.login')
                ->withErrors(['email' => 'Seu usuário não está vinculado a nenhuma empresa cliente.']);
        }

        // Disponibiliza o Client no request para uso nas páginas do portal
        $request->merge(['_portal_client' => $client]);

        return $next($request);
    }
}
