<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Illuminate\Database\Eloquent\Model;
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

    public function getTreeRecordTitle(?Model $record = null): string
    {
        if (! $record) {
            return '';
        }

        return '<strong>'.e($record->codigo).'</strong> — '.e($record->descricao);
    }
}
