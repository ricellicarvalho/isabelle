<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\HtmlString;
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
            ->path(config('panels.portal_domain') ? '' : 'portal')
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
            ->navigationGroups([
                NavigationGroup::make('Contratos')
                    ->icon('heroicon-o-document-text')
                    ->collapsed(),
                NavigationGroup::make('Documentos')
                    ->icon('heroicon-o-folder-open')
                    ->collapsed(),
                NavigationGroup::make('Financeiro')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                .fi-sidebar-group-label {
                    font-weight: 900 !important;
                    color: rgb(17 24 39) !important;
                }
                .dark .fi-sidebar-group-label {
                    color: rgb(243 244 246) !important;
                }
                .fi-sidebar-group-btn .fi-icon {
                    color: rgb(55 65 81) !important;
                }
                .dark .fi-sidebar-group-btn .fi-icon {
                    color: rgb(209 213 219) !important;
                }
                </style>
                <script>localStorage.removeItem('collapsedGroups');</script>
                HTML)
            )
            ->renderHook(
                PanelsRenderHook::BODY_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                <script>
                document.addEventListener('alpine:initialized', () => {
                    const store = window.Alpine?.store('sidebar');
                    if (!store) return;
                    const original = store.toggleCollapsedGroup.bind(store);
                    store.toggleCollapsedGroup = function (group) {
                        if (this.collapsedGroups.includes(group)) {
                            document.querySelectorAll('[data-group-label]').forEach(el => {
                                const label = el.dataset.groupLabel;
                                if (!label) return; // nunca colapsa o grupo sem label (Dashboard)
                                if (label !== group && !this.collapsedGroups.includes(label)) {
                                    this.collapseGroup(label);
                                }
                            });
                        }
                        original(group);
                    };
                });
                </script>
                HTML)
            )
            ->discoverResources(in: app_path('Filament/Portal/Resources'), for: 'App\Filament\Portal\Resources')
            ->discoverPages(in: app_path('Filament/Portal/Pages'), for: 'App\Filament\Portal\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Portal/Widgets'), for: 'App\Filament\Portal\Widgets')
            ->widgets([])
            ->middleware([
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
        // Usar config() (não env()) pois env() retorna null após config:cache.
        if ($domain = config('panels.portal_domain')) {
            $panel->domain($domain);
        }

        return $panel;
    }
}
