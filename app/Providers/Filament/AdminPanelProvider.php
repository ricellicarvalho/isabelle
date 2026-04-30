<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\FinanceStatsOverview;
use App\Filament\Widgets\OverdueReceivablesTable;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\HtmlString;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('18rem')
            ->maxContentWidth(Width::Full)
            ->default()
            ->font('Kode Mono')
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('3rem')
            ->brandName('Instituto Alves Neves')
            ->id('admin')
            ->path('admin')
            ->defaultThemeMode(ThemeMode::Light)
            ->authGuard('web')
            ->login()
            ->passwordReset()
            ->spa()
            ->profile()
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable()
                    ->timezone('America/Araguaina')
                    ->locale('pt-br'),
            ])
            ->colors([
                // Definindo a sua cor customizada com o nome 'brand'
                'brand' => [
                    50 => 'oklch(0.97 0.02 282.7)',
                    100 => 'oklch(0.94 0.04 282.7)',
                    200 => 'oklch(0.89 0.07 282.7)',
                    300 => 'oklch(0.82 0.11 282.7)',
                    400 => 'oklch(0.71 0.17 282.7)',
                    500 => 'oklch(0.589 0.232 282.7)', // Cor base (#8751d4)
                    600 => 'oklch(0.50 0.210 282.7)',
                    700 => 'oklch(0.42 0.185 282.7)',
                    800 => 'oklch(0.34 0.150 282.7)',
                    900 => 'oklch(0.27 0.110 282.7)',
                    950 => 'oklch(0.20 0.070 282.7)',
                ],
                // DICA: Se você quiser que o painel use essa cor em tudo por padrão,
                // você também pode atribuir ela ao 'primary':
                'primary' => [
                    50 => 'oklch(0.97 0.02 282.7)',
                    100 => 'oklch(0.94 0.04 282.7)',
                    200 => 'oklch(0.89 0.07 282.7)',
                    300 => 'oklch(0.82 0.11 282.7)',
                    400 => 'oklch(0.71 0.17 282.7)',
                    500 => 'oklch(0.589 0.232 282.7)',
                    600 => 'oklch(0.50 0.210 282.7)',
                    700 => 'oklch(0.42 0.185 282.7)',
                    800 => 'oklch(0.34 0.150 282.7)',
                    900 => 'oklch(0.27 0.110 282.7)',
                    950 => 'oklch(0.20 0.070 282.7)',
                ],

                // Alterando os cinzas padrão para o tom azulado (Slate)
                //'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('CRM')
                    ->icon('heroicon-o-building-office-2')
                    ->collapsed(),
                NavigationGroup::make('Financeiro')
                    ->icon('heroicon-o-banknotes')
                    ->collapsed(),
                NavigationGroup::make('Relatórios')
                    ->icon('heroicon-o-chart-bar')
                    ->collapsed(),
                NavigationGroup::make('Configurações')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->collapsed(),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                <style>
                .fi-sidebar {
                    // background-color: #FAFAFA !important; 
                    background-color: #FFFFFF !important;
                    border-right: 1px solid rgba(0, 0, 0, 0.12) !important;
                }
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            // Não usamos discoverWidgets para evitar que o CalendarWidget
            // (usado apenas dentro da CalendarPage) seja registrado no Dashboard.
            ->widgets([
                AccountWidget::class,
                FinanceStatsOverview::class,
                OverdueReceivablesTable::class,
            ])
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
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);

        // Vincula o painel Admin ao subdomínio quando configurado em produção.
        // Usar config() (não env()) pois env() retorna null após config:cache.
        if ($domain = config('panels.admin_domain')) {
            $panel->domain($domain);
        }

        return $panel;
    }
}
