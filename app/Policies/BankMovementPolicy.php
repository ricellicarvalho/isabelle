<?php

namespace App\Policies;
use App\Models\BankMovement;
use Illuminate\Foundation\Auth\User;
class BankMovementPolicy
{
    public function viewAny(User $user): bool { return $user->can('ViewAny:BankMovement'); }
    public function view(User $user, BankMovement $record): bool { return $user->can('View:BankMovement'); }
    public function create(User $user): bool { return $user->can('Create:BankMovement'); }
    public function update(User $user, BankMovement $record): bool { return $user->can('Update:BankMovement'); }
    public function delete(User $user, BankMovement $record): bool { return $user->can('Delete:BankMovement'); }
}
