<?php

namespace App\Filament\Resources\Roles;

use App\Filament\Resources\Roles\Pages\ListRoles;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\CreateRole;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends ShieldRoleResource
{
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (! auth()->user()?->hasRole('super_admin')) {
            $query->where('name', '!=', 'super_admin');
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view'   => ViewRole::route('/{record}'),
            'edit'   => EditRole::route('/{record}/edit'),
        ];
    }
}
