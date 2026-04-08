<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Domínios dos painéis Filament
    |--------------------------------------------------------------------------
    |
    | Quando preenchidos (produção), cada painel é vinculado ao seu subdomínio
    | via Panel::domain(). Vazio = roteamento por path (dev: /admin, /portal).
    |
    | Importante: estes valores são lidos via config() nos PanelProviders
    | porque env() retorna null em runtime após `php artisan config:cache`.
    |
    */

    'admin_domain' => env('ADMIN_PANEL_DOMAIN'),

    'portal_domain' => env('PORTAL_PANEL_DOMAIN'),

];
