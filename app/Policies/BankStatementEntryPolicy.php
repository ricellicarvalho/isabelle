<?php

namespace App\Policies;
use App\Models\BankStatementEntry;
use Illuminate\Foundation\Auth\User;
class BankStatementEntryPolicy
{
    public function viewAny(User $user): bool { return $user->can('ViewAny:BankStatementEntry'); }
    public function view(User $user, BankStatementEntry $record): bool { return $user->can('View:BankStatementEntry'); }
    public function update(User $user, BankStatementEntry $record): bool { return $user->can('Update:BankStatementEntry'); }
}
