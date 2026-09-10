<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Category;
use App\Models\Payable;
use App\Models\User;
use App\Services\BankMovementService;
use App\Services\OfxImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BankMovementModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_paid_title_creates_only_one_legacy_movement_after_cutoff(): void
    {
        [$user, $category, $account] = $this->baseData('100.00');
        $payable = Payable::create(['category_id' => $category->id, 'bank_account_id' => $account->id, 'descricao' => 'Internet', 'valor' => 80, 'valor_pago' => 80, 'data_vencimento' => '2026-08-05', 'data_pagamento' => '2026-08-05', 'forma_pagamento' => 'pix', 'status' => 'pago', 'created_by' => $user->id]);

        $service = app(BankMovementService::class);
        $service->syncLegacyPaid($payable);
        $service->syncLegacyPaid($payable->fresh());

        $this->assertDatabaseCount('financial_settlements', 1);
        $this->assertDatabaseCount('bank_movements', 1);
        $this->assertSame('20.00', $account->fresh()->balanceAt());
    }

    public function test_title_before_cutoff_does_not_create_movement(): void
    {
        [$user, $category, $account] = $this->baseData();
        $payable = Payable::create(['category_id' => $category->id, 'bank_account_id' => $account->id, 'descricao' => 'Legado', 'valor' => 10, 'data_vencimento' => '2026-07-01', 'data_pagamento' => '2026-07-31', 'status' => 'pago', 'created_by' => $user->id]);
        $this->assertNull(app(BankMovementService::class)->syncLegacyPaid($payable));
        $this->assertDatabaseCount('bank_movements', 0);
    }

    public function test_transfer_creates_balanced_linked_movements(): void
    {
        [, , $from] = $this->baseData('100.00');
        $to = $this->account('Sicoob', '4221.34');
        app(BankMovementService::class)->transfer($from, $to, '2026-08-10', '40.00');
        $this->assertDatabaseCount('bank_movements', 2);
        $this->assertSame('60.00', $from->fresh()->balanceAt());
        $this->assertSame('4261.34', $to->fresh()->balanceAt());
        $this->assertSame(1, BankMovement::distinct()->count('transfer_group'));
    }

    public function test_ofx_import_is_idempotent(): void
    {
        [, , $account] = $this->baseData();
        $ofx = '<OFX><BANKTRANLIST><STMTTRN><TRNTYPE>CREDIT<DTPOSTED>20260805120000<TRNAMT>150.50<FITID>abc-1<MEMO>PIX CLIENTE</STMTTRN></BANKTRANLIST></OFX>';
        app(OfxImportService::class)->import($account, $ofx, 'agosto.ofx');
        $this->assertDatabaseCount('bank_statement_entries', 1);
        $this->expectException(ValidationException::class);
        app(OfxImportService::class)->import($account, $ofx, 'agosto.ofx');
    }

    public function test_partial_settlements_in_different_accounts_keep_principal_and_cash_separate(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $other = $this->account('Sicoob', '100.00');
        $service = app(BankMovementService::class);
        $first = $service->settle($title, $account, '2026-08-10', '40.00', 'pix', ['interest' => '2.00', 'penalty' => '1.00', 'discount' => '3.00', 'fee' => '0.50']);
        $this->assertSame('40.50', $first->amount);
        $this->assertSame('60.00', $title->fresh()->saldo_aberto);
        $this->assertSame('Parcial', $title->fresh()->situacao_financeira);
        $service->settle($title, $other, '2026-08-09', '60.00');
        $this->assertSame('0.00', $title->fresh()->saldo_aberto);
        $this->assertSame('pago', $title->fresh()->status);
        $this->assertSame('100.50', $title->fresh()->valor_pago);
        $this->assertSame('2026-08-10', $title->fresh()->data_pagamento->toDateString());
        $this->assertSame('59.50', $account->balanceAt());
        $this->assertSame('40.00', $other->balanceAt());
        $service->syncLegacyPaid($title->fresh());
        $this->assertDatabaseCount('financial_settlements', 2);
    }

    public function test_stale_title_cannot_settle_more_than_remaining_balance(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $stale = $title->fresh();
        app(BankMovementService::class)->settle($title, $account, '2026-08-10', '60.00');
        try {
            app(BankMovementService::class)->settle($stale, $account, '2026-08-10', '60.00');
            $this->fail('Deveria rejeitar saldo insuficiente.');
        } catch (ValidationException $e) {
            $this->assertSame('40.00', $title->fresh()->saldo_aberto);
            $this->assertDatabaseCount('bank_movements', 1);
        }
    }

    public function test_retry_of_partial_request_does_not_duplicate_it(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $service = app(BankMovementService::class);
        $first = $service->settle($title, $account, '2026-08-10', '20.00', 'pix', ['idempotency_key' => 'request-1']);
        $retry = $service->settle($title->fresh(), $account, '2026-08-10', '20.00', 'pix', ['idempotency_key' => 'request-1']);
        $this->assertSame($first->id, $retry->id);
        $this->assertSame('80.00', $title->fresh()->saldo_aberto);
        $this->assertDatabaseCount('bank_movements', 1);
    }

    public function test_reversal_keeps_audit_and_allows_new_settlement(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $this->actingAs(User::find($title->created_by));
        $service = app(BankMovementService::class);
        $settlement = $service->settle($title, $account, '2026-08-10', '100.00');
        $service->reverse($settlement, 'Conta informada incorretamente');
        $service->reverse($settlement, 'Repetição');
        $this->assertSame('100.00', $account->balanceAt());
        $this->assertSame('100.00', $title->fresh()->saldo_aberto);
        $this->assertSame('reversed', $settlement->fresh()->movement->status);
        $this->assertSame($title->created_by, $settlement->fresh()->reversed_by);
        $this->assertSame('Conta informada incorretamente', $settlement->fresh()->reversal_reason);
        $this->assertNotNull($settlement->fresh()->reversed_at);
        $service->settle($title, $account, '2026-08-11', '100.00');
        $this->assertDatabaseCount('financial_settlements', 2);
        $this->assertDatabaseCount('bank_movements', 2);
        $this->assertSame('0.00', $account->balanceAt());
    }

    public function test_reconciled_settlement_cannot_be_reversed_or_edited(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $this->actingAs(User::find($title->created_by));
        $settlement = app(BankMovementService::class)->settle($title, $account, '2026-08-10', '100.00');
        app(OfxImportService::class)->import($account, '<OFX><STMTTRN><DTPOSTED>20260810<TRNAMT>-100.00<FITID>debit-1</STMTTRN></OFX>', 'debito.ofx');
        $entry = \App\Models\BankStatementEntry::firstOrFail();
        app(\App\Services\BankReconciliationService::class)->reconcile($entry, $settlement->movement);
        try {
            app(BankMovementService::class)->reverse($settlement, 'Correção');
            $this->fail('Estorno conciliado deveria ser bloqueado.');
        } catch (ValidationException $e) {
            $this->assertSame('confirmed', $settlement->fresh()->status);
            $this->assertSame('0.00', $account->balanceAt());
        }
        $this->expectException(ValidationException::class);
        $settlement->movement->update(['amount' => '10.00']);
    }

    public function test_title_with_settlements_rejects_direct_financial_changes_but_accepts_notes(): void
    {
        [$title, $account] = $this->unpaidTitle();
        app(BankMovementService::class)->settle($title, $account, '2026-08-10', '20.00');
        $title = $title->fresh();
        $title->update(['observacoes' => 'Conferido']);
        $this->assertSame('Conferido', $title->fresh()->observacoes);
        $this->expectException(ValidationException::class);
        $title->update(['valor' => '200.00']);
    }

    public function test_settlement_rejects_inactive_account_cutoff_and_invalid_precision(): void
    {
        [$title, $account] = $this->unpaidTitle();
        foreach ([['2026-07-31', '10.00'], ['2026-08-10', '0.001'], ['2026-08-10', '-1'], ['2026-08-10', '101']] as [$date, $amount]) {
            try {
                app(BankMovementService::class)->settle($title, $account, $date, $amount);
                $this->fail('Baixa inválida aceita.');
            } catch (ValidationException $e) {
                $this->assertDatabaseCount('financial_settlements', 0);
            }
        }
        $account->update(['ativo' => false]);
        $this->expectException(ValidationException::class);
        app(BankMovementService::class)->settle($title, $account, '2026-08-10', '10');
    }

    public function test_cnab_reimport_is_idempotent_and_preserves_adjustments(): void
    {
        [$payable, $account] = $this->unpaidTitle();
        $client = \App\Models\Client::create(['tipo_pessoa' => 'pj', 'cnpj_cpf' => '12345678000199', 'razao_social' => 'Cliente teste', 'status' => 'ativo', 'created_by' => $payable->created_by]);
        $title = \App\Models\Receivable::create(['client_id' => $client->id, 'category_id' => $payable->category_id, 'descricao' => 'Recebimento', 'valor' => '100.00', 'data_vencimento' => '2026-08-10', 'status' => 'pendente', 'created_by' => $payable->created_by]);
        $boleto = \App\Models\BankBoleto::create(['receivable_id' => $title->id, 'nosso_numero' => '12345', 'numero_documento' => '1', 'carteira' => '09', 'valor' => '100.00', 'data_vencimento' => '2026-08-10', 'status' => 'emitido', 'created_by' => $payable->created_by]);
        $retorno = \App\Models\BankRetorno::create(['bank_account_id' => $account->id, 'nome_arquivo' => 'retorno.ret', 'caminho_arquivo' => 'retorno.ret', 'data_processamento' => now(), 'created_by' => $payable->created_by]);
        $detalhe = new \Eduardokum\LaravelBoleto\Cnab\Retorno\Detalhe('');
        $detalhe->setTipoOcorrencia(1);
        $detalhe->nossoNumero = '12345';
        $detalhe->valorRecebido = '102.00';
        $detalhe->valorMora = '5.00';
        $detalhe->valorDesconto = '3.00';
        $detalhe->valorTarifa = '1.00';
        $detalhe->dataCredito = \Carbon\Carbon::parse('2026-08-12');
        $service = new class extends \App\Services\CnabRetornoService
        {
            public function apply($detail, $return): array
            {
                return $this->aplicarDetalhe($detail, $return);
            }
        };
        $service->apply($detalhe, $retorno);
        $service->apply($detalhe, $retorno);
        $this->assertDatabaseCount('financial_settlements', 1);
        $this->assertSame('100.00', $title->settlements()->first()->principal_amount);
        $this->assertSame('101.00', $title->settlements()->first()->amount);
        $this->assertSame('0.00', $title->fresh()->saldo_aberto);
        $this->assertSame('201.00', $account->balanceAt());
        $this->assertSame('2026-08-12', $title->fresh()->data_pagamento->toDateString());
    }

    public function test_payable_actions_render_settle_and_show_audit_history(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $user = User::find($title->created_by);
        \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user->assignRole('super_admin');
        foreach (['ViewAny:Payable', 'View:Payable', 'Update:Payable', 'Settle:Payable', 'Reverse:Payable'] as $permission) {
            $user->givePermissionTo(\Spatie\Permission\Models\Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $this->actingAs($user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\Payables\Pages\ListPayables::class)
            ->assertSuccessful()
            ->callAction(\Filament\Actions\Testing\TestAction::make('darBaixa')->table($title), data: [
                'bank_account_id' => $account->id, 'date' => '2026-08-10', 'amount' => '40.00',
                'interest' => '0.00', 'penalty' => '0.00', 'discount' => '0.00', 'fee' => '0.00',
                'method' => 'pix', 'idempotency_key' => 'ui-request',
            ])
            ->assertHasNoActionErrors();
        $this->assertSame('60.00', $title->fresh()->saldo_aberto);
        \Livewire\Livewire::test(\App\Filament\Resources\Payables\Pages\EditPayable::class, ['record' => $title->id])
            ->assertSuccessful()
            ->mountAction('historicoBaixas')
            ->assertActionMounted('historicoBaixas')
            ->unmountAction()
            ->callAction('estornarBaixa', data: ['settlement_id' => $title->settlements()->first()->id, 'reason' => 'Correção pela interface'])
            ->assertHasNoActionErrors();
        $this->assertSame('100.00', $title->fresh()->saldo_aberto);
        $this->assertStringContainsString('Correção pela interface', view('filament.financial-settlements.history', ['settlements' => $title->settlements()->get()])->render());
    }

    public function test_paid_legacy_title_exposes_historical_settlement_action(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $title->newQuery()->whereKey($title->id)->update([
            'status' => 'pago',
            'valor_pago' => $title->valor,
            'data_pagamento' => '2026-08-10',
            'forma_pagamento' => 'pix',
        ]);
        $title->refresh();
        $user = User::find($title->created_by);
        $user->syncRoles([\Spatie\Permission\Models\Role::firstOrCreate([
            'name' => 'super_admin',
            'guard_name' => 'web',
        ])]);
        $this->actingAs($user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        \Livewire\Livewire::test(\App\Filament\Resources\Payables\Pages\ListPayables::class)
            ->assertSuccessful()
            ->assertActionVisible(\Filament\Actions\Testing\TestAction::make('darBaixa')->table($title))
            ->callAction(\Filament\Actions\Testing\TestAction::make('darBaixa')->table($title), data: [
                'bank_account_id' => $account->id, 'date' => '2026-08-10', 'amount' => $title->valor,
                'interest' => '0.00', 'penalty' => '0.00', 'discount' => '0.00', 'fee' => '0.00',
                'method' => 'pix', 'idempotency_key' => 'ignored-for-legacy',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame('legacy_migration', $title->settlements()->first()->origin);
        $this->assertDatabaseCount('bank_movements', 1);
    }

    public function test_reversal_permissions_require_explicit_grant_and_survive_reseeding(): void
    {
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $role = \Spatie\Permission\Models\Role::findByName('financeiro');
        $this->assertTrue($role->hasPermissionTo('Settle:Payable'));
        $this->assertFalse($role->hasPermissionTo('Reverse:Payable'));
        $role->givePermissionTo('Reverse:Payable');
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->assertTrue($role->fresh()->hasPermissionTo('Reverse:Payable'));
    }

    public function test_reversed_and_deleted_history_cannot_be_reactivated_by_retry_or_removed(): void
    {
        [$title, $account] = $this->unpaidTitle();
        $this->actingAs(User::find($title->created_by));
        $service = app(BankMovementService::class);
        $settlement = $service->settle($title, $account, '2026-08-10', '20.00', null, ['idempotency_key' => 'reversed-request']);
        $service->reverse($settlement, 'Correção');
        try {
            $service->settle($title, $account, '2026-08-10', '20.00', null, ['idempotency_key' => 'reversed-request']);
            $this->fail('Repetição não pode reativar estorno.');
        } catch (ValidationException $e) {
            $this->assertSame('100.00', $title->fresh()->saldo_aberto);
        }
        $this->expectException(ValidationException::class);
        $title->delete();
    }

    private function unpaidTitle(): array
    {
        [$user, $category, $account] = $this->baseData('100.00');
        $title = Payable::create(['category_id' => $category->id, 'descricao' => 'Serviço', 'valor' => '100.00', 'data_vencimento' => '2026-08-10', 'status' => 'pendente', 'created_by' => $user->id]);

        return [$title, $account];
    }

    private function baseData(string $opening = '0.00'): array
    {
        $user = User::factory()->create();
        $category = Category::create(['codigo' => '3', 'descricao' => 'Despesas', 'tipo' => 'despesa', 'order' => 1, 'ativo' => true, 'created_by' => $user->id]);

        return [$user, $category, $this->account('Bradesco', $opening, $user)];
    }

    private function account(string $name, string $opening, ?User $user = null): BankAccount
    {
        $user ??= User::factory()->create();

        return BankAccount::create(['nome' => $name, 'banco' => $name === 'Sicoob' ? '756' : '237', 'descricao' => $name, 'tipo' => 'conta_corrente', 'agencia' => '0001', 'conta' => fake()->unique()->numerify('#####'), 'opening_balance_date' => '2026-07-31', 'opening_balance' => $opening, 'ativo' => true, 'created_by' => $user->id]);
    }
}
