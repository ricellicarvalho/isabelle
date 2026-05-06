<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ClientDocument;
use Illuminate\Auth\Access\HandlesAuthorization;

class ClientDocumentPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:ClientDocument');
    }

    public function view(AuthUser $authUser, ClientDocument $clientDocument): bool
    {
        return $authUser->can('View:ClientDocument');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:ClientDocument');
    }

    public function update(AuthUser $authUser, ClientDocument $clientDocument): bool
    {
        return $authUser->can('Update:ClientDocument');
    }

    public function delete(AuthUser $authUser, ClientDocument $clientDocument): bool
    {
        return $authUser->can('Delete:ClientDocument');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:ClientDocument');
    }

    public function restore(AuthUser $authUser, ClientDocument $clientDocument): bool
    {
        return $authUser->can('Restore:ClientDocument');
    }

    public function forceDelete(AuthUser $authUser, ClientDocument $clientDocument): bool
    {
        return $authUser->can('ForceDelete:ClientDocument');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:ClientDocument');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:ClientDocument');
    }

    public function replicate(AuthUser $authUser, ClientDocument $clientDocument): bool
    {
        return $authUser->can('Replicate:ClientDocument');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:ClientDocument');
    }

}