<?php

namespace App\Filament\Portal\Pages;

use App\Filament\Portal\Widgets\PortalNr1Widget;
use App\Filament\Portal\Widgets\PortalStatsWidget;
use App\Models\Client;
use BackedEnum;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\HtmlString;

class Dashboard extends BaseDashboard
{
    protected static string|BackedEnum|null $navigationIcon  = Heroicon::Home;
    protected static ?string               $navigationLabel = 'Início';
    protected static ?string               $title           = 'Painel de Controle';
    protected static ?int                  $navigationSort  = -1;

    public function getWidgets(): array
    {
        return [
            PortalStatsWidget::class,
            PortalNr1Widget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 1;
    }

    public function getHeader(): ?\Illuminate\Contracts\View\View
    {
        return null;
    }

    public function getHeading(): string|HtmlString
    {
        $client = Client::where('portal_user_id', Auth::id())->first();
        $nome   = $client?->nome_fantasia ?: $client?->razao_social ?? 'Cliente';

        return new HtmlString(
            '<div style="display:flex; flex-direction:column; gap:.25rem;">'
            . '<div style="display:flex; align-items:center; gap:.75rem;">'
            . '<div style="width:42px; height:42px; border-radius:.625rem; background:linear-gradient(135deg,#f59e0b,#d97706); display:flex; align-items:center; justify-content:center; flex-shrink:0;">'
            . '<svg style="width:22px; height:22px; color:white;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'
            . '</div>'
            . '<div>'
            . '<h1 style="font-size:1.25rem; font-weight:800; color:#111827; margin:0; line-height:1.2;">Olá, ' . e($nome) . '!</h1>'
            . '<p style="font-size:.8125rem; color:#6b7280; margin:0;">Bem-vindo ao seu portal de acompanhamento NR-1</p>'
            . '</div>'
            . '</div>'
            . '</div>'
        );
    }
}
