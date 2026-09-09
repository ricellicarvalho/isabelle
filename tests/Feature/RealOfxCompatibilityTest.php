<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\User;
use App\Services\OfxImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RealOfxCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_available_real_bank_files_import_safely_and_idempotently(): void
    {
        $files = [
            'docs/Bradesco_03092026_144009.OFX',
            'docs/extrato-conta-corrente-ofx-money_202609_20260903143848.ofx',
        ];
        if (collect($files)->contains(fn (string $path) => ! is_file(base_path($path)))) {
            $this->markTestSkipped('Arquivos OFX reais não estão disponíveis neste ambiente.');
        }
        $user = User::factory()->create();
        foreach ($files as $index => $path) {
            $contents = file_get_contents(base_path($path));
            preg_match('/<BANKID>\s*([^<\r\n]+)/i', $contents, $bankMatch);
            preg_match('/<ACCTID>\s*([^<\r\n]+)/i', $contents, $accountMatch);
            $account = BankAccount::create([
                'nome' => 'Conta homologação '.($index + 1), 'banco' => ltrim(trim($bankMatch[1]), '0'),
                'agencia' => '1', 'conta' => trim($accountMatch[1]),
                'opening_balance_date' => '2026-07-31', 'opening_balance' => '0.00',
                'ativo' => true, 'created_by' => $user->id,
            ]);
            $import = app(OfxImportService::class)->import($account, $contents, basename($path), $user->id);
            $this->assertGreaterThan(0, $import->entries()->count());
            $this->assertSame($import->entries()->count(), $import->entries()->distinct()->count('fit_id'));
            $this->assertNotNull($import->ledger_balance);
            $this->assertNotNull($import->ledger_balance_at);
            $this->assertFalse($import->entries()->get()->contains(fn ($entry) => ! mb_check_encoding($entry->memo ?? '', 'UTF-8')));
            try {
                app(OfxImportService::class)->import($account, $contents, basename($path), $user->id);
                $this->fail('Reimportação deveria ser bloqueada.');
            } catch (ValidationException) {
                $this->assertDatabaseCount('bank_statement_imports', $index + 1);
            }
        }
    }
}
