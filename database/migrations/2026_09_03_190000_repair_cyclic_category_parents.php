<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $selfReferencing = DB::table('categories')
            ->whereColumn('id', 'parent_id')
            ->get(['id', 'codigo']);

        $parents = DB::table('categories')
            ->pluck('parent_id', 'id')
            ->mapWithKeys(fn ($parentId, $id): array => [(int) $id => $parentId === null ? null : (int) $parentId])
            ->all();

        $cyclicIds = [];

        foreach (array_keys($parents) as $startId) {
            $path = [];
            $positions = [];
            $currentId = $startId;

            while ($currentId !== null && isset($parents[$currentId])) {
                if (isset($positions[$currentId])) {
                    foreach (array_slice($path, $positions[$currentId]) as $cyclicId) {
                        $cyclicIds[$cyclicId] = true;
                    }

                    break;
                }

                $positions[$currentId] = count($path);
                $path[] = $currentId;
                $currentId = $parents[$currentId];
            }
        }

        if ($cyclicIds !== []) {
            DB::table('categories')
                ->whereIn('id', array_keys($cyclicIds))
                ->update(['parent_id' => null]);
        }

        // Selecting the record itself in the form also generated a child code
        // (for example, the root code "2" became "2.3"). Restore that prefix
        // when it is not already used by another category.
        foreach ($selfReferencing as $category) {
            $rootCode = preg_replace('/\.[^.]+$/', '', $category->codigo);

            if ($rootCode === $category->codigo || DB::table('categories')->where('codigo', $rootCode)->exists()) {
                continue;
            }

            DB::table('categories')->where('id', $category->id)->update(['codigo' => $rootCode]);
        }
    }

    public function down(): void
    {
        // Invalid cyclic relationships must not be restored.
    }
};
