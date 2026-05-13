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

        Role::firstOrCreate(['name' => 'super_admin',        'guard_name' => 'web']);
        $administrador = Role::firstOrCreate(['name' => 'administrador',      'guard_name' => 'web']);
        $financeiro    = Role::firstOrCreate(['name' => 'financeiro',         'guard_name' => 'web']);
        $colaborador   = Role::firstOrCreate(['name' => 'colaborador',        'guard_name' => 'web']);
        $seguranca     = Role::firstOrCreate(['name' => 'seguranca_trabalho', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cliente',            'guard_name' => 'web']);

        // super_admin bypasses all gates via Shield config — no explicit permissions needed.

        $crmResources      = ['Client', 'Contract', 'Event', 'ClientDocument'];
        $financeiroRes     = ['BankAccount', 'BankBoleto', 'BankRemessa', 'BankRetorno',
                              'Receivable', 'Payable', 'Nfse', 'NfseConfig', 'NfseServiceCode'];
        $fullActions       = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny',
                              'Restore', 'RestoreAny', 'ForceDelete', 'ForceDeleteAny', 'Reorder'];
        $readWrite         = ['ViewAny', 'View', 'Create', 'Update'];
        $financeiroReports = ['View:DreReport', 'View:CashFlowReport',
                              'View:FinanceStatsOverview', 'View:OverdueReceivablesTable'];
        $userActions       = ['ViewAny', 'View', 'Create', 'Update', 'Delete', 'DeleteAny'];

        $exists = fn (string $p): bool => Permission::where('name', $p)->exists();

        // ── Administrador: CRM + Financeiro + Relatórios + Gestão de Usuários ──────
        $adminPerms = [];
        foreach (array_merge($crmResources, $financeiroRes, ['Pricing', 'Category', 'Supplier']) as $res) {
            foreach ($fullActions as $action) {
                $adminPerms[] = "{$action}:{$res}";
            }
        }
        foreach ($financeiroReports as $perm) {
            $adminPerms[] = $perm;
        }
        foreach ($userActions as $action) {
            $adminPerms[] = "{$action}:User";
        }
        $adminPerms[] = 'View:CalendarPage';

        $administrador->syncPermissions(array_filter($adminPerms, $exists));

        // ── Financeiro: CRM + Financeiro + Relatórios + Pricing + Category ─────────
        $finPerms = [];
        foreach (array_merge($crmResources, $financeiroRes, ['Pricing', 'Category']) as $res) {
            foreach ($fullActions as $action) {
                $finPerms[] = "{$action}:{$res}";
            }
        }
        foreach ($financeiroReports as $perm) {
            $finPerms[] = $perm;
        }
        $finPerms[] = 'View:CalendarPage';

        $financeiro->syncPermissions(array_filter($finPerms, $exists));

        // ── Colaborador: CRM leitura/escrita + Calendário ─────────────────────────
        $colabPerms = [];
        foreach ($crmResources as $res) {
            foreach ($readWrite as $action) {
                $colabPerms[] = "{$action}:{$res}";
            }
        }
        $colabPerms[] = 'ViewAny:Pricing';
        $colabPerms[] = 'View:Pricing';
        $colabPerms[] = 'View:CalendarPage';

        $colaborador->syncPermissions(array_filter($colabPerms, $exists));

        // ── Segurança do Trabalho: mesma base do colaborador (ajustes via Shield UI)
        $seguranca->syncPermissions(array_filter($colabPerms, $exists));
    }
}
