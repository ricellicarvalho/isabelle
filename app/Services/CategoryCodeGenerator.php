<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;

class CategoryCodeGenerator
{
    public function next(?int $parentId = null, bool $lock = false): string
    {
        $parent = $parentId
            ? Category::withTrashed()->findOrFail($parentId)
            : null;

        $query = Category::withTrashed()
            ->when(
                $parentId === null,
                fn (Builder $query): Builder => $query->whereNull('parent_id'),
                fn (Builder $query): Builder => $query->where('parent_id', $parentId),
            );

        if ($lock) {
            $query->lockForUpdate();
        }

        $prefix = $parent ? $parent->codigo.'.' : '';
        $maiorSequencia = $query
            ->pluck('codigo')
            ->map(function (string $codigo) use ($prefix): ?int {
                if ($prefix !== '' && ! str_starts_with($codigo, $prefix)) {
                    return null;
                }

                $segmento = $prefix === '' ? $codigo : substr($codigo, strlen($prefix));

                return preg_match('/^\d+$/', $segmento) ? (int) $segmento : null;
            })
            ->filter(fn (?int $sequencia): bool => $sequencia !== null)
            ->max() ?? 0;

        return $prefix.($maiorSequencia + 1);
    }
}
