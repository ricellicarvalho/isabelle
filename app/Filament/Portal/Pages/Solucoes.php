<?php

namespace App\Filament\Portal\Pages;

use BackedEnum;
use Filament\Pages\Page;

class Solucoes extends Page
{
    protected string $view = 'filament.portal.pages.solucoes';

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $navigationLabel = 'Soluções';

    protected static ?string $title = 'Soluções para sua empresa';

    protected static ?int $navigationSort = 0;
}
