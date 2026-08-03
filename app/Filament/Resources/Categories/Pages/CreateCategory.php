<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use App\Services\CategoryCodeGenerator;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        $data['codigo'] = app(CategoryCodeGenerator::class)->next(
            filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null,
            lock: true,
        );

        return $data;
    }
}
