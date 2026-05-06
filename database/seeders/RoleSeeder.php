<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $consultor  = Role::firstOrCreate(['name' => 'consultor',   'guard_name' => 'web']);
        $operacional = Role::firstOrCreate(['name' => 'operacional', 'guard_name' => 'web']);

        // super_admin bypasses all gates via Shield config — no explicit permissions needed.

        // Financeiro resources (operacional is BLOCKED — RN06)
        $financeiro = [
            'BankAccount', 'BankBoleto', 'BankRemessa', 'BankRetorno',
            'Receivable', 'Payable', 'Nfse', 'NfseConfig', 'NfseServiceCode',
        ];
        $financeiroReports = [
            'View:DreReport', 'View:CashFlowReport',
            'View:FinanceStatsOverview', 'View:OverdueReceivablesTable',
        ];

        $crmResources = ['Client', 'Contract', 'Event', 'ClientDocument'];
        $crmActions   = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
                         'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Reorder'];

        $crmReadWrite = ['ViewAny', 'View', 'Create', 'Update']; // operacional: no delete

        // --- consultor: CRM + Financeiro + Reports + Pricing + Category + Supplier ---
        $consultorPerms = [];

        foreach ($crmResources as $resource) {
            foreach ($crmActions as $action) {
                $consultorPerms[] = "{$action}:{$resource}";
            }
        }

        foreach ($financeiro as $resource) {
            foreach ($crmActions as $action) {
                $consultorPerms[] = "{$action}:{$resource}";
            }
        }

        foreach ($financeiroReports as $perm) {
            $consultorPerms[] = $perm;
        }

        foreach (['Pricing', 'Category', 'Supplier'] as $resource) {
            foreach ($crmActions as $action) {
                $consultorPerms[] = "{$action}:{$resource}";
            }
        }

        $consultorPerms[] = 'View:CalendarPage';

        $consultorPerms = array_filter(
            $consultorPerms,
            fn ($p) => Permission::where('name', $p)->exists()
        );

        $consultor->syncPermissions($consultorPerms);

        // --- operacional: CRM read/write (no delete) + Calendar + Pricing (view) ---
        $operacionalPerms = [];

        foreach ($crmResources as $resource) {
            foreach ($crmReadWrite as $action) {
                $operacionalPerms[] = "{$action}:{$resource}";
            }
        }

        $operacionalPerms[] = 'ViewAny:Pricing';
        $operacionalPerms[] = 'View:Pricing';
        $operacionalPerms[] = 'ViewAny:Category';
        $operacionalPerms[] = 'View:Category';
        $operacionalPerms[] = 'View:CalendarPage';

        $operacionalPerms = array_filter(
            $operacionalPerms,
            fn ($p) => Permission::where('name', $p)->exists()
        );

        $operacional->syncPermissions($operacionalPerms);
    }
}
