<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Pricing;
use Illuminate\Auth\Access\HandlesAuthorization;

class PricingPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('ViewAny:Pricing');
    }

    public function view(AuthUser $authUser, Pricing $pricing): bool
    {
        return $authUser->can('View:Pricing');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('Create:Pricing');
    }

    public function update(AuthUser $authUser, Pricing $pricing): bool
    {
        return $authUser->can('Update:Pricing');
    }

    public function delete(AuthUser $authUser, Pricing $pricing): bool
    {
        return $authUser->can('Delete:Pricing');
    }

    public function deleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('DeleteAny:Pricing');
    }

    public function restore(AuthUser $authUser, Pricing $pricing): bool
    {
        return $authUser->can('Restore:Pricing');
    }

    public function forceDelete(AuthUser $authUser, Pricing $pricing): bool
    {
        return $authUser->can('ForceDelete:Pricing');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('ForceDeleteAny:Pricing');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('RestoreAny:Pricing');
    }

    public function replicate(AuthUser $authUser, Pricing $pricing): bool
    {
        return $authUser->can('Replicate:Pricing');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('Reorder:Pricing');
    }

}