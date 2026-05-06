<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BankRemessa;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankRemessaPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BankRemessa');
    }

    public function view(AuthUser $authUser, BankRemessa $bankRemessa): bool
    {
        return $authUser->can('View:BankRemessa');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BankRemessa');
    }

    public function update(AuthUser $authUser, BankRemessa $bankRemessa): bool
    {
        return $authUser->can('Update:BankRemessa');
    }

    public function delete(AuthUser $authUser, BankRemessa $bankRemessa): bool
    {
        return $authUser->can('Delete:BankRemessa');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BankRemessa');
    }

    public function restore(AuthUser $authUser, BankRemessa $bankRemessa): bool
    {
        return $authUser->can('Restore:BankRemessa');
    }

    public function forceDelete(AuthUser $authUser, BankRemessa $bankRemessa): bool
    {
        return $authUser->can('ForceDelete:BankRemessa');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BankRemessa');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BankRemessa');
    }

    public function replicate(AuthUser $authUser, BankRemessa $bankRemessa): bool
    {
        return $authUser->can('Replicate:BankRemessa');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BankRemessa');
    }

}