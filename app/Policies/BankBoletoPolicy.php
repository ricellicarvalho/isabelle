<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\BankBoleto;
use Illuminate\Auth\Access\HandlesAuthorization;

class BankBoletoPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:BankBoleto');
    }

    public function view(AuthUser $authUser, BankBoleto $bankBoleto): bool
    {
        return $authUser->can('View:BankBoleto');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:BankBoleto');
    }

    public function update(AuthUser $authUser, BankBoleto $bankBoleto): bool
    {
        return $authUser->can('Update:BankBoleto');
    }

    public function delete(AuthUser $authUser, BankBoleto $bankBoleto): bool
    {
        return $authUser->can('Delete:BankBoleto');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:BankBoleto');
    }

    public function restore(AuthUser $authUser, BankBoleto $bankBoleto): bool
    {
        return $authUser->can('Restore:BankBoleto');
    }

    public function forceDelete(AuthUser $authUser, BankBoleto $bankBoleto): bool
    {
        return $authUser->can('ForceDelete:BankBoleto');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:BankBoleto');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:BankBoleto');
    }

    public function replicate(AuthUser $authUser, BankBoleto $bankBoleto): bool
    {
        return $authUser->can('Replicate:BankBoleto');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:BankBoleto');
    }

}