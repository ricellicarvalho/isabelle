<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BankRetorno;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankRetornoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BankRetorno');
    }

    public function view(AuthUser $authUser, BankRetorno $bankRetorno): bool
    {
        return $authUser->can('View:BankRetorno');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BankRetorno');
    }

    public function update(AuthUser $authUser, BankRetorno $bankRetorno): bool
    {
        return $authUser->can('Update:BankRetorno');
    }

    public function delete(AuthUser $authUser, BankRetorno $bankRetorno): bool
    {
        return $authUser->can('Delete:BankRetorno');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BankRetorno');
    }

    public function restore(AuthUser $authUser, BankRetorno $bankRetorno): bool
    {
        return $authUser->can('Restore:BankRetorno');
    }

    public function forceDelete(AuthUser $authUser, BankRetorno $bankRetorno): bool
    {
        return $authUser->can('ForceDelete:BankRetorno');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BankRetorno');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BankRetorno');
    }

    public function replicate(AuthUser $authUser, BankRetorno $bankRetorno): bool
    {
        return $authUser->can('Replicate:BankRetorno');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BankRetorno');
    }

}