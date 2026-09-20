<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        $role = Role::where('name', 'administrador')->where('guard_name', 'web')->first();

        if (! $role) {
            return;
        }

        $permissions = Permission::where('guard_name', 'web')
            ->whereIn('name', [
                'ViewAny:ClientDocument',
                'View:ClientDocument',
                'Create:ClientDocument',
                'Update:ClientDocument',
                'Delete:ClientDocument',
            ])->get();

        $role->givePermissionTo($permissions);
    }

    public function down(): void
    {
        // As permissões existentes antes desta correção devem ser preservadas.
    }
};
