<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Nfse;
use Illuminate\Auth\Access\HandlesAuthorization;

class NfsePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Nfse');
    }

    public function view(AuthUser $authUser, Nfse $nfse): bool
    {
        return $authUser->can('View:Nfse');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Nfse');
    }

    public function update(AuthUser $authUser, Nfse $nfse): bool
    {
        return $authUser->can('Update:Nfse');
    }

    public function delete(AuthUser $authUser, Nfse $nfse): bool
    {
        return $authUser->can('Delete:Nfse');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Nfse');
    }

    public function restore(AuthUser $authUser, Nfse $nfse): bool
    {
        return $authUser->can('Restore:Nfse');
    }

    public function forceDelete(AuthUser $authUser, Nfse $nfse): bool
    {
        return $authUser->can('ForceDelete:Nfse');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Nfse');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Nfse');
    }

    public function replicate(AuthUser $authUser, Nfse $nfse): bool
    {
        return $authUser->can('Replicate:Nfse');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Nfse');
    }

}