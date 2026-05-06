<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\NfseServiceCode;
use Illuminate\Auth\Access\HandlesAuthorization;

class NfseServiceCodePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NfseServiceCode');
    }

    public function view(AuthUser $authUser, NfseServiceCode $nfseServiceCode): bool
    {
        return $authUser->can('View:NfseServiceCode');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NfseServiceCode');
    }

    public function update(AuthUser $authUser, NfseServiceCode $nfseServiceCode): bool
    {
        return $authUser->can('Update:NfseServiceCode');
    }

    public function delete(AuthUser $authUser, NfseServiceCode $nfseServiceCode): bool
    {
        return $authUser->can('Delete:NfseServiceCode');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NfseServiceCode');
    }

    public function restore(AuthUser $authUser, NfseServiceCode $nfseServiceCode): bool
    {
        return $authUser->can('Restore:NfseServiceCode');
    }

    public function forceDelete(AuthUser $authUser, NfseServiceCode $nfseServiceCode): bool
    {
        return $authUser->can('ForceDelete:NfseServiceCode');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NfseServiceCode');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NfseServiceCode');
    }

    public function replicate(AuthUser $authUser, NfseServiceCode $nfseServiceCode): bool
    {
        return $authUser->can('Replicate:NfseServiceCode');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NfseServiceCode');
    }

}