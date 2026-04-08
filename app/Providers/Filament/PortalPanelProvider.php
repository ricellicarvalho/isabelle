<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PortalPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->id('portal')
            ->path('portal')
            ->authGuard('portal')
            ->login()
            ->brandName('Portal do Cliente')
            ->brandLogo(asset('images/logo2.png'))
            ->brandLogoHeight('3.5rem')
            ->favicon(asset('images/favicon.ico'))
            ->font('Kode Mono')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Portal/Widgets'), for: 'App\Filament\Portal\Widgets')
            ->widgets([])
            ->middleware([
                // Define o cookie de sessão exclusivo do Portal antes do StartSession.
                \App\Http\Middleware\SetSessionCookieName::class.':isabelle_portal_session',
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                \App\Http\Middleware\EnsurePortalClient::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Vincula o painel Portal ao subdomínio quando configurado em produção.
        if ($domain = env('PORTAL_PANEL_DOMAIN')) {
            $panel->domain($domain);
        }

        return $panel;
    }
}
