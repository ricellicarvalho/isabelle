<?php

namespace Tests\Feature;

use App\Exports\BankAccountReportExport;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\Category;
use App\Models\Client;
use App\Models\Receivable;
use App\Models\User;
use App\Services\BankAccountReportService;
use App\Services\BankMovementService;
use App\Services\BankReconciliationService;
use App\Services\CashFlowService;
use App\Services\DashboardFinanceService;
use App\Services\DreService;
use App\Services\OfxImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankAccountReportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private BankAccount $account;

    private Category $income;

    private Category $expense;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2026-09-09 12:00:00');
        $this->user = User::factory()->create();
        $this->account = $this->account('Conta A', '100.00');
        $this->income = Category::create(['codigo' => '1', 'descricao' => 'Serviços', 'tipo' => 'receita', 'ativo' => true, 'created_by' => $this->user->id]);
        $this->expense = Category::create(['codigo' => '3', 'descricao' => 'Despesas', 'tipo' => 'despesa', 'ativo' => true, 'created_by' => $this->user->id]);
        $this->client = Client::create(['cnpj_cpf' => '12345678000199', 'razao_social' => 'Cliente de teste', 'created_by' => $this->user->id]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_account_report_has_exact_balances_order_and_selected_totals(): void
    {
        $this->movement('credit', '10.00', '2026-08-01 12:00:00');
        $first = $this->movement('debit', '20.15', '2026-08-05 10:00:00', ['reference' => 'DOC-1', 'counterparty' => 'Fornecedor A']);
        $second = $this->movement('credit', '30.25', '2026-08-05 10:00:00');
        $this->movement('credit', '999.00', '2026-08-05 12:00:00', ['status' => 'reversed']);
        $this->movement('credit', '888.00', '2026-08-05 12:00:00', ['status' => 'planned']);
        $this->movement('credit', '777.00', '2026-08-05 12:00:00')->delete();
        $this->movement('credit', '0.10', '2026-08-31 23:59:59');
        $report = BankAccountReportService::generate($this->filters(['data_inicio' => '2026-08-02']));
        $this->assertSame('110.00', $report['opening']);
        $this->assertSame('20.15', $report['debits']);
        $this->assertSame('30.35', $report['credits']);
        $this->assertSame('120.20', $report['closing']);
        $this->assertSame('170.20', $report['available']);
        $this->assertSame([$first->id, $second->id], array_slice(array_column($report['rows'], 'id'), 0, 2));
        $this->assertSame('89.85', $report['rows'][0]['balance']);
        $this->assertSame('120.20', $this->account->balanceAt('2026-08-31'));
        $filtered = BankAccountReportService::generate($this->filters(['data_inicio' => '2026-08-02', 'search' => 'doc-1', 'category_id' => $this->expense->id]));
        $this->assertCount(1, $filtered['rows']);
        $this->assertSame('120.20', $filtered['closing']);
        $this->assertSame('20.15', $filtered['selected_debits']);
        $this->assertSame('0.00', $filtered['selected_credits']);
    }

    public function test_reconciliation_filter_and_empty_inactive_account_report(): void
    {
        $movement = $this->movement('credit', '10.00', '2026-08-05 12:00:00');
        $import = app(OfxImportService::class)->import($this->account, '<OFX><STMTTRN><DTPOSTED>20260805<TRNAMT>10.00<FITID>rpt-1</STMTTRN></OFX>', 'test.ofx');
        app(BankReconciliationService::class)->reconcile($import->entries()->first(), $movement);
        $report = BankAccountReportService::generate($this->filters(['reconciliation' => 'reconciled']));
        $this->assertCount(1, $report['rows']);
        $this->assertSame('Conciliado', $report['rows'][0]['reconciliation']);
        $this->account->update(['ativo' => false]);
        $empty = BankAccountReportService::generate($this->filters(['data_inicio' => '2026-09-01', 'data_fim' => '2026-09-30']));
        $this->assertCount(0, $empty['rows']);
        $this->assertSame('110.00', $empty['opening']);
        $this->assertSame('110.00', $empty['closing']);
    }

    public function test_cutoff_bridge_does_not_double_count_legacy_or_unclassified_titles(): void
    {
        Receivable::create(['client_id' => $this->client->id, 'category_id' => $this->income->id, 'descricao' => 'Legado', 'valor' => '40.00', 'valor_pago' => '40.00', 'data_vencimento' => '2026-07-01', 'data_pagamento' => '2026-07-31', 'status' => 'pago', 'created_by' => $this->user->id]);
        Receivable::create(['client_id' => $this->client->id, 'category_id' => $this->income->id, 'descricao' => 'A classificar', 'valor' => '999.00', 'valor_pago' => '999.00', 'data_vencimento' => '2026-08-01', 'data_pagamento' => '2026-08-02', 'status' => 'pago', 'created_by' => $this->user->id]);
        $title = $this->receivable();
        app(BankMovementService::class)->settle($title, $this->account, '2026-08-03', '20.00');
        $report = CashFlowService::generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-08-31'));
        $this->assertSame('60.00', $report['totais']['entradas']);
        $this->assertSame('120.00', $report['totais']['saldo_final']);
        $this->assertSame('60.00', $report['ajuste_abertura']);
        $this->assertSame(1, $report['unclassified']);
        $this->assertSame('abertura', $report['linhas'][1]['tipo']);
        $july = DreService::generate(Carbon::parse('2026-07-01'), Carbon::parse('2026-07-31'));
        $this->assertSame('40.00', $july['totais']['receitas']);
        $august = DreService::generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
        $this->assertSame('20.00', $august['totais']['receitas']);
    }

    public function test_transfers_do_not_change_dre_or_consolidated_cash_and_partials_use_actual_account(): void
    {
        $other = $this->account('Conta B', '200.00');
        $title = $this->receivable();
        $service = app(BankMovementService::class);
        $service->settle($title, $this->account, '2026-08-03', '20.00');
        $service->settle($title, $other, '2026-08-04', '30.00');
        $service->transfer($this->account, $other, '2026-08-05', '40.00');
        $start = Carbon::parse('2026-08-01');
        $end = Carbon::parse('2026-08-31');
        $dre = DreService::generate($start, $end);
        $this->assertSame('50.00', $dre['totais']['receitas']);
        $this->assertSame('0.00', $dre['totais']['despesas']);
        $cash = CashFlowService::generate($start, $end);
        $this->assertSame('300.00', $cash['saldo_inicial']);
        $this->assertSame('50.00', $cash['totais']['entradas']);
        $this->assertSame('0.00', $cash['totais']['saidas']);
        $this->assertSame('350.00', $cash['totais']['saldo_final']);
        $a = CashFlowService::generate($start, $end, accountId: $this->account->id);
        $this->assertSame('20.00', $a['totais']['entradas']);
        $this->assertSame('40.00', $a['totais']['saidas']);
        $this->assertSame('80.00', $a['totais']['saldo_final']);
        $dashboard = app(DashboardFinanceService::class)->summary($start, $end, $this->account->id);
        $this->assertSame('20.00', $dashboard['receitas']);
        $this->assertSame('50.00', $dashboard['receber_vencidos']['total']);
        $this->assertSame('20.00', DreService::generate($start, $end, $this->account->id)['totais']['receitas']);
    }

    public function test_reversal_and_manual_fee_are_reflected_in_all_reports(): void
    {
        $this->actingAs($this->user);
        $service = app(BankMovementService::class);
        $settlement = $service->settle($this->receivable(), $this->account, '2026-08-05', '30.00');
        $service->reverse($settlement, 'Teste');
        $this->movement('debit', '0.15', '2026-08-05 12:00:00');
        $start = Carbon::parse('2026-08-01');
        $end = Carbon::parse('2026-08-31');
        $this->assertSame('99.85', BankAccountReportService::generate($this->filters())['closing']);
        $this->assertSame('99.85', CashFlowService::generate($start, $end)['totais']['saldo_final']);
        $this->assertSame('-0.15', DreService::generate($start, $end)['totais']['lucro_liquido']);
        $this->assertSame('-0.15', app(DashboardFinanceService::class)->summary($start, $end)['resultado']);
    }

    public function test_report_rejects_invalid_period_and_period_before_opening(): void
    {
        foreach ([['data_inicio' => '2026-07-31'], ['data_fim' => '2026-07-31'], ['bank_account_id' => null]] as $filters) {
            try {
                BankAccountReportService::generate($this->filters($filters));
                $this->fail('Filtro inválido aceito.');
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors());
            }
        }
        $this->account->update(['opening_balance_date' => '2026-08-05']);
        $this->expectException(ValidationException::class);
        BankAccountReportService::generate($this->filters());
    }

    public function test_pdf_requires_permission_even_with_valid_signature_and_renders_real_pdf(): void
    {
        $url = URL::temporarySignedRoute('reports.bank-account.pdf', now()->addMinutes(5), $this->filters());
        $this->actingAs($this->user)->get($url)->assertForbidden();
        $this->grantReportAccess();
        $this->movement('credit', '12.34', '2026-08-05 12:00:00');
        $response = $this->get($url)->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        $this->get(route('reports.bank-account.pdf', $this->filters()))->assertForbidden();
    }

    public function test_page_renders_same_totals_and_excel_preserves_references_as_text(): void
    {
        $this->grantReportAccess();
        $this->actingAs($this->user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        $this->movement('credit', '12.34', '2026-08-05 12:00:00', ['reference' => '=1+1']);
        \Livewire\Livewire::test(\App\Filament\Pages\BankAccountReport::class)
            ->fillForm($this->filters())->call('generateReport')->assertHasNoFormErrors()->assertSee('112,34');
        $report = BankAccountReportService::generate($this->filters());
        $bytes = \Maatwebsite\Excel\Facades\Excel::raw(new BankAccountReportExport($report), \Maatwebsite\Excel\Excel::XLSX);
        $path = tempnam(sys_get_temp_dir(), 'bank-report-');
        file_put_contents($path, $bytes);
        try {
            $sheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($path)->getActiveSheet();
            $this->assertSame('=1+1', $sheet->getCell('B7')->getValue());
            $this->assertSame('s', $sheet->getCell('B7')->getDataType());
            $this->assertSame('n', $sheet->getCell('D7')->getDataType());
            $this->assertEquals(12.34, $sheet->getCell('D7')->getValue());
        } finally {
            unlink($path);
        }
    }

    public function test_opening_of_an_additional_account_is_not_revenue(): void
    {
        $other = $this->account('Conta nova', '75.00');
        $other->update(['opening_balance_date' => '2026-08-10']);
        $report = CashFlowService::generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'));
        $this->assertSame('100.00', $report['saldo_inicial']);
        $this->assertSame('75.00', $report['ajuste_abertura']);
        $this->assertSame('175.00', $report['totais']['saldo_final']);
        $this->assertSame('0.00', $report['totais']['entradas']);
        $this->assertSame('0.00', $other->balanceAt('2026-08-09'));
        $next = CashFlowService::generate(Carbon::parse('2026-08-11'), Carbon::parse('2026-08-31'));
        $this->assertSame('175.00', $next['saldo_inicial']);
        $this->assertSame('0.00', $next['ajuste_abertura']);
    }

    public function test_account_title_filter_matches_earlier_partial_settlement(): void
    {
        $other = $this->account('Conta B', '0.00');
        $title = $this->receivable();
        $service = app(BankMovementService::class);
        $service->settle($title, $this->account, '2026-08-03', '20.00');
        $service->settle($title, $other, '2026-08-04', '30.00');
        $unrelated = $this->receivable();
        $service->settle($unrelated, $other, '2026-08-05', '10.00');
        foreach (['ViewAny:Receivable', 'View:Receivable'] as $permission) {
            $this->user->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }
        $this->actingAs($this->user);
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));
        \Livewire\Livewire::test(\App\Filament\Resources\Receivables\Pages\ListReceivables::class)
            ->filterTable('bank_account_id', $this->account->id)
            ->assertCanSeeTableRecords([$title])
            ->assertCanNotSeeTableRecords([$unrelated]);
        $competence = CashFlowService::generate(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31'), 'competencia', '0.00', $this->account->id);
        $this->assertSame('100.00', $competence['totais']['entradas']);
    }

    private function account(string $name, string $opening): BankAccount
    {
        return BankAccount::create(['nome' => $name, 'banco' => '237', 'agencia' => '1', 'conta' => uniqid(), 'opening_balance_date' => '2026-07-31', 'opening_balance' => $opening, 'credit_limit' => '50.00', 'ativo' => true, 'created_by' => $this->user->id]);
    }

    private function receivable(): Receivable
    {
        return Receivable::create(['client_id' => $this->client->id, 'category_id' => $this->income->id, 'descricao' => 'Serviço', 'valor' => '100.00', 'data_vencimento' => '2026-08-01', 'status' => 'pendente', 'created_by' => $this->user->id]);
    }

    private function movement(string $direction, string $amount, string $date, array $extra = []): BankMovement
    {
        return BankMovement::create($extra + ['bank_account_id' => $this->account->id, 'category_id' => $direction === 'credit' ? $this->income->id : $this->expense->id, 'direction' => $direction, 'amount' => $amount, 'occurred_at' => $date, 'origin' => 'manual', 'description' => 'Movimento de teste', 'status' => 'confirmed', 'created_by' => $this->user->id]);
    }

    private function filters(array $extra = []): array
    {
        return $extra + ['bank_account_id' => $this->account->id, 'data_inicio' => '2026-08-01', 'data_fim' => '2026-08-31'];
    }

    private function grantReportAccess(): void
    {
        $this->user->givePermissionTo(Permission::firstOrCreate(['name' => 'View:BankAccountReport', 'guard_name' => 'web']));
    }
}
