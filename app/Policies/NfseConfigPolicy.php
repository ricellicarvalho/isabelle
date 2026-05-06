<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\NfseConfig;
use Illuminate\Auth\Access\HandlesAuthorization;

class NfseConfigPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:NfseConfig');
    }

    public function view(AuthUser $authUser, NfseConfig $nfseConfig): bool
    {
        return $authUser->can('View:NfseConfig');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:NfseConfig');
    }

    public function update(AuthUser $authUser, NfseConfig $nfseConfig): bool
    {
        return $authUser->can('Update:NfseConfig');
    }

    public function delete(AuthUser $authUser, NfseConfig $nfseConfig): bool
    {
        return $authUser->can('Delete:NfseConfig');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:NfseConfig');
    }

    public function restore(AuthUser $authUser, NfseConfig $nfseConfig): bool
    {
        return $authUser->can('Restore:NfseConfig');
    }

    public function forceDelete(AuthUser $authUser, NfseConfig $nfseConfig): bool
    {
        return $authUser->can('ForceDelete:NfseConfig');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:NfseConfig');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:NfseConfig');
    }

    public function replicate(AuthUser $authUser, NfseConfig $nfseConfig): bool
    {
        return $authUser->can('Replicate:NfseConfig');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:NfseConfig');
    }

}