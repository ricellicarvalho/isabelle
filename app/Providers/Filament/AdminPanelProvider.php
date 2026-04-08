<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\FinanceStatsOverview;
use App\Filament\Widgets\OverdueReceivablesTable;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->maxContentWidth(Width::Full)
            ->default()
            ->font('Kode Mono')
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo2.png'))
            ->brandLogoHeight('4rem')
            ->brandName('Instituto Alves Neves')
            ->id('admin')
            ->path('admin')
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
                'gray' => Color::Slate,
            ])
            ->navigationGroups([
                NavigationGroup::make('CRM'),
                NavigationGroup::make('Financeiro'),
                NavigationGroup::make('Relatórios'),
                NavigationGroup::make('Configurações')->collapsed(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FinanceStatsOverview::class,
                OverdueReceivablesTable::class,
            ])
            ->middleware([
                // Define o cookie de sessão exclusivo do painel Admin antes do StartSession.
                \App\Http\Middleware\SetSessionCookieName::class.':isabelle_admin_session',
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
        if ($domain = env('ADMIN_PANEL_DOMAIN')) {
            $panel->domain($domain);
        }

        return $panel;
    }
}
