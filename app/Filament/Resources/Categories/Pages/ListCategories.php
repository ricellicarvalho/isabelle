<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use SolutionForest\FilamentTree\Resources\Pages\TreePage as BasePage;

class ListCategories extends BasePage
{
    protected static string $resource = CategoryResource::class;

    public function getTitle(): string
    {
        return 'Plano de Contas';
    }

    public function getHeading(): string
    {
        return 'Plano de Contas';
    }

    public static function getNavigationLabel(): string
    {
        return 'Plano de Contas';
    }

    public static function getMaxDepth(): int
    {
        return 4;
    }
}
