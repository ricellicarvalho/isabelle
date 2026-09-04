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
