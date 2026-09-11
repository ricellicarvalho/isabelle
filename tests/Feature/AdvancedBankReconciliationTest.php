<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankReconciliationAllocation;
use App\Models\BankStatementEntry;
use App\Models\BankStatementImport;
use App\Models\User;
use App\Services\BankReconciliationService;
use App\Services\OfxImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdvancedBankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BankAccount $account;

    private BankReconciliationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        $this->account = BankAccount::create([
            'nome' => 'Conta teste', 'banco' => '237', 'agencia' => '1', 'conta' => '88942',
            'opening_balance_date' => '2026-07-31', 'opening_balance' => '100.00', 'ativo' => true, 'created_by' => $this->user->id,
        ]);
        $this->service = app(BankReconciliationService::class);
    }

    public function test_one_statement_entry_can_be_allocated_to_many_movements_and_only_completed_at_zero(): void
    {
        $entry = $this->entry('-100.00', '2026-08-05', 'DOC-ABC', 'Fornecedor Azul');
        $forty = $this->movement('40.00', 'debit', '2026-08-05', 'DOC-ABC', 'Fornecedor Azul');
        $sixty = $this->movement('60.00', 'debit', '2026-08-06');
        $suggestions = $this->service->suggestions($entry);
        $this->assertSame($forty->id, $suggestions->first()['movement']->id);
        $this->service->allocate($entry, [['movement_id' => $forty->id, 'amount' => '40.00']]);
        $this->assertSame('60.00', $this->service->entryRemaining($entry));
        try {
            $this->service->complete($entry);
            $this->fail('Conciliação parcial não pode ser concluída.');
        } catch (ValidationException) {
            $this->assertSame('pending', $entry->fresh()->status);
        }
        $this->service->allocate($entry, [['movement_id' => $sixty->id, 'amount' => '60.00']]);
        $this->service->complete($entry);
        $this->assertSame('reconciled', $entry->fresh()->status);
        $this->assertDatabaseCount('bank_reconciliation_allocations', 2);
    }

    public function test_many_entries_can_share_one_movement_without_overallocation(): void
    {
        $first = $this->entry('30.00', '2026-08-05');
        $second = $this->entry('70.00', '2026-08-06');
        $movement = $this->movement('100.00', 'credit', '2026-08-05');
        $this->service->allocate($first, [['movement_id' => $movement->id, 'amount' => '30.00']]);
        $this->service->complete($first);
        $this->service->allocate($second, [['movement_id' => $movement->id, 'amount' => '70.00']]);
        $this->service->complete($second);
        $this->assertSame('0.00', $this->service->movementRemaining($movement));
        $third = $this->entry('1.00', '2026-08-07');
        $this->expectException(ValidationException::class);
        $this->service->allocate($third, [['movement_id' => $movement->id, 'amount' => '1.00']]);
    }

    public function test_undo_preserves_audit_and_allows_reallocation(): void
    {
        $entry = $this->entry('-25.00', '2026-08-05');
        $movement = $this->movement('25.00', 'debit', '2026-08-05');
        $this->service->reconcile($entry, $movement);
        $this->service->undo($entry, 'Conta interna incorreta');
        $allocation = BankReconciliationAllocation::firstOrFail();
        $this->assertNotNull($allocation->reversed_at);
        $this->assertSame($this->user->id, $allocation->reversed_by);
        $this->assertSame('Conta interna incorreta', $allocation->reversal_reason);
        $this->assertSame(['allocated', 'completed', 'undone'], $entry->events()->orderBy('id')->pluck('action')->all());
        $this->assertSame('pending', $entry->fresh()->status);
        $this->assertSame('25.00', $this->service->movementRemaining($movement));
        $this->service->reconcile($entry, $movement);
        $this->assertDatabaseCount('bank_reconciliation_allocations', 2);
    }

    public function test_archive_is_justified_reversible_and_rejects_allocated_line(): void
    {
        $entry = $this->entry('10.00', '2026-08-05');
        $this->service->archive($entry, 'Linha informativa do banco');
        $this->assertSame('archived', $entry->fresh()->status);
        $this->assertNotNull($entry->fresh()->archived_at);
        $this->service->unarchive($entry);
        $this->assertSame('pending', $entry->fresh()->status);
        $this->assertSame(['archived', 'unarchived'], $entry->events()->orderBy('id')->pluck('action')->all());
        $this->service->allocate($entry, [['movement_id' => $this->movement('10.00', 'credit', '2026-08-05')->id, 'amount' => '5.00']]);
        $this->expectException(ValidationException::class);
        $this->service->archive($entry, 'Não deve arquivar');
    }

    public function test_daily_comparison_uses_ofx_balance_and_system_ledger(): void
    {
        $ofx = '<OFX><BANKID>237<ACCTID>88942<BANKTRANLIST>'
            .'<STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260801<TRNAMT>10.00<FITID>a</STMTTRN>'
            .'<STMTTRN><TRNTYPE>DEBIT<DTPOSTED>20260802<TRNAMT>-5.00<FITID>b</STMTTRN>'
            .'</BANKTRANLIST><LEDGERBAL><BALAMT>105.00<DTASOF>20260802</LEDGERBAL></OFX>';
        app(OfxImportService::class)->import($this->account, $ofx, 'test.ofx');
        $this->movement('10.00', 'credit', '2026-08-01');
        $rows = $this->service->dailyComparison($this->account, Carbon::parse('2026-08-01'), Carbon::parse('2026-08-03'));
        $this->assertSame('110.00', $rows[0]['bank']);
        $this->assertSame('110.00', $rows[0]['system']);
        $this->assertSame('0.00', $rows[0]['difference']);
        $this->assertSame('105.00', $rows[2]['bank']);
        $this->assertSame('-5.00', $rows[2]['difference']);
    }

    public function test_ofx_validates_account_and_parses_decimal_comma_and_legacy_encoding(): void
    {
        $ofx = mb_convert_encoding('<OFX><BANKID>0237<ACCTID>88942<BANKTRANLIST><STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260803<TRNAMT>10,25<FITID>x<MEMO>CRÉDITO</STMTTRN></BANKTRANLIST><LEDGERBAL><BALAMT>110,25<DTASOF>00000000</LEDGERBAL></OFX>', 'Windows-1252', 'UTF-8');
        $import = app(OfxImportService::class)->import($this->account, $ofx, 'legacy.ofx');
        $this->assertSame('10.25', $import->entries()->first()->amount);
        $this->assertSame('110.25', $import->ledger_balance);
        $this->assertSame('CRÉDITO', $import->entries()->first()->memo);
        $wrong = BankAccount::create(['nome' => 'Outra', 'banco' => '756', 'agencia' => '1', 'conta' => '1', 'ativo' => true, 'created_by' => $this->user->id]);
        $this->expectException(ValidationException::class);
        app(OfxImportService::class)->import($wrong, $ofx, 'wrong.ofx');
    }

    public function test_filament_allocation_and_daily_comparison_pages_render(): void
    {
        foreach (['ViewAny:BankStatementEntry', 'View:BankStatementEntry', 'Reconcile:BankStatementEntry'] as $name) {
            $this->user->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $entry = $this->entry('10.00', '2026-08-05');
        $movement = $this->movement('10.00', 'credit', '2026-08-05');
        $this->assertArrayHasKey($movement->id, \App\Filament\Resources\BankStatementEntries\BankStatementEntryResource::movementOptions($entry));
        $this->assertSame('10.00', \App\Filament\Resources\BankMovements\Schemas\BankMovementForm::parseMoney('10,00'));
        \Livewire\Livewire::test(\App\Filament\Resources\BankStatementEntries\Pages\ListBankStatementEntries::class)
            ->assertSuccessful()
            ->mountAction(\Filament\Actions\Testing\TestAction::make('allocate')->table($entry))
            ->assertActionMounted(\Filament\Actions\Testing\TestAction::make('allocate')->table($entry))
            ->unmountAction();
        $this->service->allocate($entry, [['movement_id' => $movement->id, 'amount' => '10.00']]);
        \Livewire\Livewire::test(\App\Filament\Resources\BankStatementEntries\Pages\ListBankStatementEntries::class)
            ->callAction(\Filament\Actions\Testing\TestAction::make('complete')->table($entry))
            ->assertHasNoActionErrors();
        $this->assertSame('reconciled', $entry->fresh()->status);
        \Livewire\Livewire::test(\App\Filament\Pages\BankReconciliationSummary::class)
            ->fillForm(['bank_account_id' => $this->account->id, 'data_inicio' => '2026-08-01', 'data_fim' => '2026-08-31'])
            ->call('generate')->assertHasNoFormErrors()->assertSee('Banco');
    }

    public function test_reconciliation_permissions_keep_undo_restricted_and_preserve_manual_grant(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $role = \Spatie\Permission\Models\Role::findByName('financeiro');
        $this->assertTrue($role->hasPermissionTo('Reconcile:BankStatementEntry'));
        $this->assertTrue($role->hasPermissionTo('Archive:BankStatementEntry'));
        $this->assertFalse($role->hasPermissionTo('UndoReconciliation:BankStatementEntry'));
        $role->givePermissionTo('UndoReconciliation:BankStatementEntry');
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->assertTrue($role->fresh()->hasPermissionTo('UndoReconciliation:BankStatementEntry'));
    }

    public function test_super_admin_can_access_financial_pages_and_actions_through_gate(): void
    {
        $superAdmin = \Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ]);
        $this->user->syncRoles([$superAdmin]);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $this->assertTrue($this->user->can('View:BankAccountReport'));
        $this->assertTrue($this->user->can('ViewAny:BankStatementEntry'));
        $this->assertTrue($this->user->can('Settle:Payable'));
        $this->assertTrue($this->user->can('Settle:Receivable'));
    }

    private function entry(string $amount, string $date, ?string $document = null, ?string $memo = null): BankStatementEntry
    {
        $import = BankStatementImport::firstOrCreate(['bank_account_id' => $this->account->id, 'file_hash' => str_repeat('a', 63).BankStatementImport::count()], ['filename' => 'test.ofx', 'created_by' => $this->user->id]);

        return BankStatementEntry::create(['bank_statement_import_id' => $import->id, 'bank_account_id' => $this->account->id, 'fit_id' => uniqid(), 'occurred_at' => $date, 'amount' => $amount, 'document' => $document, 'memo' => $memo, 'status' => 'pending']);
    }

    private function movement(string $amount, string $direction, string $date, ?string $reference = null, ?string $counterparty = null): BankMovement
    {
        return BankMovement::create(['bank_account_id' => $this->account->id, 'occurred_at' => $date, 'direction' => $direction, 'amount' => $amount, 'origin' => 'manual', 'description' => 'Movimento teste', 'reference' => $reference, 'counterparty' => $counterparty, 'status' => 'confirmed', 'created_by' => $this->user->id]);
    }
}
