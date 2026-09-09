<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankStatementEntry;
use App\Models\BankStatementImport;
use App\Models\Category;
use App\Models\Payable;
use App\Models\User;
use App\Services\BankMovementService;
use App\Services\BankReconciliationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialSettlementConcurrencyTest extends TestCase
{
    public function test_simultaneous_settlements_cannot_overpay(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! function_exists('pcntl_fork')) {
            $this->markTestSkipped('Exige MySQL e pcntl para testar bloqueio real entre processos.');
        }
        $this->assertSame('isabelle_test', DB::connection()->getDatabaseName(), 'Concorrência somente no banco exclusivo de testes.');
        $user = User::factory()->create();
        $category = Category::create(['codigo' => 'conc-'.uniqid(), 'descricao' => 'Concorrência', 'tipo' => 'despesa', 'order' => 1, 'ativo' => true, 'created_by' => $user->id]);
        $account = BankAccount::create(['nome' => 'Teste concorrência', 'banco' => '237', 'agencia' => '1', 'conta' => uniqid(), 'ativo' => true, 'created_by' => $user->id]);
        $title = Payable::create(['category_id' => $category->id, 'descricao' => 'Concorrência', 'valor' => '100.00', 'data_vencimento' => '2026-08-10', 'status' => 'pendente', 'created_by' => $user->id]);
        DB::disconnect();
        $workers = [];
        for ($i = 0; $i < 2; $i++) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
            $pid = pcntl_fork();
            if ($pid === 0) {
                fclose($sockets[0]);
                fread($sockets[1], 1);
                try {
                    DB::purge();
                    DB::statement('SET SESSION innodb_lock_wait_timeout = 5');
                    app(BankMovementService::class)->settle($title, $account, '2026-08-10', '60.00');
                    fwrite($sockets[1], 'settled');
                } catch (ValidationException $e) {
                    fwrite($sockets[1], 'rejected');
                } catch (\Throwable $e) {
                    fwrite($sockets[1], 'error:'.$e->getMessage());
                }
                fclose($sockets[1]);
                exit(0);
            }
            $this->assertGreaterThan(0, $pid);
            fclose($sockets[1]);
            stream_set_timeout($sockets[0], 15);
            $workers[] = [$pid, $sockets[0]];
        }
        foreach ($workers as [$pid, $socket]) {
            fwrite($socket, '1');
        }
        $results = [];
        foreach ($workers as [$pid, $socket]) {
            $results[] = stream_get_contents($socket);
            fclose($socket);
            pcntl_waitpid($pid, $status);
        }
        DB::purge();
        sort($results);
        $this->assertSame(['rejected', 'settled'], $results);
        $this->assertSame('40.00', $title->fresh()->saldo_aberto);
        $this->assertSame(1, $title->settlements()->count());
    }

    public function test_concurrent_reconciliation_allocations_cannot_exceed_the_movement(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql' || ! extension_loaded('pcntl')) {
            $this->markTestSkipped('A concorrência real da conciliação exige MySQL e PCNTL.');
        }
        $this->assertSame('isabelle_test', DB::connection()->getDatabaseName());
        $user = User::factory()->create();
        $account = BankAccount::create([
            'nome' => 'Conta conciliação '.uniqid(), 'banco' => '237', 'agencia' => '1',
            'conta' => uniqid(), 'opening_balance_date' => '2026-07-31', 'opening_balance' => '0.00',
            'ativo' => true, 'created_by' => $user->id,
        ]);
        $movement = BankMovement::create([
            'bank_account_id' => $account->id, 'occurred_at' => '2026-08-10', 'direction' => 'credit',
            'amount' => '100.00', 'origin' => 'manual', 'description' => 'Crédito compartilhado',
            'status' => 'confirmed', 'created_by' => $user->id,
        ]);
        $import = BankStatementImport::create([
            'bank_account_id' => $account->id, 'filename' => 'concorrencia.ofx',
            'file_hash' => hash('sha256', uniqid('', true)), 'created_by' => $user->id,
        ]);
        $entries = collect([1, 2])->map(fn (int $number) => BankStatementEntry::create([
            'bank_statement_import_id' => $import->id, 'bank_account_id' => $account->id,
            'fit_id' => 'concurrent-'.$number.'-'.uniqid(), 'occurred_at' => '2026-08-10',
            'amount' => '60.00', 'status' => 'pending',
        ]));

        DB::disconnect();
        $workers = [];
        foreach ($entries as $entry) {
            $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);
            $pid = pcntl_fork();
            if ($pid === 0) {
                fclose($sockets[0]);
                fread($sockets[1], 1);
                try {
                    DB::purge();
                    DB::statement('SET SESSION innodb_lock_wait_timeout = 10');
                    app(BankReconciliationService::class)->allocate($entry, [[
                        'movement_id' => $movement->id, 'amount' => '60.00',
                    ]]);
                    fwrite($sockets[1], 'allocated');
                } catch (ValidationException) {
                    fwrite($sockets[1], 'rejected');
                } catch (\Throwable $exception) {
                    fwrite($sockets[1], 'error:'.$exception->getMessage());
                }
                fclose($sockets[1]);
                exit(0);
            }
            $this->assertGreaterThan(0, $pid);
            fclose($sockets[1]);
            stream_set_timeout($sockets[0], 15);
            $workers[] = [$pid, $sockets[0]];
        }
        foreach ($workers as [$pid, $socket]) {
            fwrite($socket, '1');
        }
        $results = [];
        foreach ($workers as [$pid, $socket]) {
            $results[] = stream_get_contents($socket);
            fclose($socket);
            pcntl_waitpid($pid, $status);
        }

        DB::purge();
        sort($results);
        $this->assertSame(['allocated', 'rejected'], $results);
        $this->assertDatabaseCount('bank_reconciliation_allocations', 1);
        $this->assertSame('40.00', app(BankReconciliationService::class)->movementRemaining($movement->fresh()));
    }
}
