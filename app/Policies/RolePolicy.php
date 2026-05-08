<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Role');
    }

    public function view(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('View:Role');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Role');
    }

    public function update(AuthUser $authUser, Role $role): bool
    {
        if (! $authUser->can('Update:Role')) {
            return false;
        }

        // Somente super_admin pode editar o próprio role super_admin
        if ($role->name === 'super_admin') {
            return $authUser->hasRole('super_admin');
        }

        // Administrador não pode editar o role administrador (apenas super_admin pode)
        if ($role->name === 'administrador' && ! $authUser->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public function delete(AuthUser $authUser, Role $role): bool
    {
        if (! $authUser->can('Delete:Role')) {
            return false;
        }

        // O role super_admin nunca pode ser excluído
        if ($role->name === 'super_admin') {
            return false;
        }

        // Administrador não pode excluir o role administrador
        if ($role->name === 'administrador' && ! $authUser->hasRole('super_admin')) {
            return false;
        }

        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Role') && $authUser->hasRole('super_admin');
    }

    public function restore(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Restore:Role');
    }

    public function forceDelete(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('ForceDelete:Role');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Role');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Role');
    }

    public function replicate(AuthUser $authUser, Role $role): bool
    {
        return $authUser->can('Replicate:Role');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Role');
    }

}