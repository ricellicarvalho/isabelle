<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Foundation\Auth\User as AuthUser;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:User');
    }

    public function view(AuthUser $authUser): bool
    {
        return $authUser->can('View:User');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:User');
    }

    public function update(AuthUser $authUser, User $user): bool
    {
        if (! $authUser->can('Update:User')) {
            return false;
        }

        // Ninguém além do super_admin edita admin@isabelle.com.br
        if ($user->email === 'admin@isabelle.com.br' && ! $authUser->hasRole('super_admin')) {
            return false;
        }

        // Administrador não edita super_admin nem outros administradores
        if ($authUser->hasRole('administrador') && $user->hasAnyRole(['super_admin', 'administrador'])) {
            return false;
        }

        return true;
    }

    public function delete(AuthUser $authUser, User $user): bool
    {
        if (! $authUser->can('Delete:User')) {
            return false;
        }

        // Usuário admin@isabelle.com.br nunca pode ser excluído
        if ($user->email === 'admin@isabelle.com.br') {
            return false;
        }

        // Administrador não exclui super_admin nem outros administradores
        if ($authUser->hasRole('administrador') && $user->hasAnyRole(['super_admin', 'administrador'])) {
            return false;
        }

        return true;
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:User')
            && $authUser->hasAnyRole(['super_admin', 'administrador']);
    }

    public function restore(AuthUser $authUser): bool
    {
        return $authUser->can('Restore:User');
    }

    public function forceDelete(AuthUser $authUser, User $user): bool
    {
        if (! $authUser->can('ForceDelete:User')) {
            return false;
        }

        return $user->email !== 'admin@isabelle.com.br'
            && $authUser->hasRole('super_admin');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:User')
            && $authUser->hasRole('super_admin');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:User');
    }

    public function replicate(AuthUser $authUser): bool
    {
        return $authUser->can('Replicate:User');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:User');
    }
}
