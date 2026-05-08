<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ListRoles as ShieldListRoles;
use Illuminate\Database\Eloquent\Builder;

class ListRoles extends ShieldListRoles
{
    protected static string $resource = RoleResource::class;

    protected function getTableQuery(): Builder
    {
        $query = parent::getTableQuery();

        if (! auth()->user()?->hasRole('super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        return $query;
    }
}
