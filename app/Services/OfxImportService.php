<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\BankStatementImport;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OfxImportService
{
    public function import(BankAccount $account, string $contents, string $filename, ?int $userId = null): BankStatementImport
    {
        if (strlen($contents) > 10 * 1024 * 1024) {
            throw ValidationException::withMessages(['ofx' => 'O arquivo OFX deve ter no máximo 10 MB.']);
        }
        $hash = hash('sha256', $contents);
        if (BankStatementImport::where('bank_account_id', $account->id)->where('file_hash', $hash)->exists()) {
            throw ValidationException::withMessages(['ofx' => 'Este arquivo OFX já foi importado nesta conta.']);
        }
        $text = mb_check_encoding($contents, 'UTF-8') ? $contents : mb_convert_encoding($contents, 'UTF-8', 'Windows-1252,ISO-8859-1');
        $bankId = $this->tag($text, 'BANKID');
        $accountNumber = $this->tag($text, 'ACCTID');
        $this->validateAccount($account, $bankId, $accountNumber);
        preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>)|(?=<\/BANKTRANLIST>))/si', $text, $matches);
        if (empty($matches[1])) {
            throw ValidationException::withMessages(['ofx' => 'O arquivo não contém movimentações OFX reconhecíveis.']);
        }
        $rows = collect($matches[1])->map(function (string $block): array {
            return [
                'fit_id' => $this->tag($block, 'FITID') ?: hash('sha256', $block),
                'occurred_at' => $this->date($this->tag($block, 'DTPOSTED'), 'data da movimentação'),
                'amount' => $this->money($this->tag($block, 'TRNAMT')),
                'type' => $this->tag($block, 'TRNTYPE'),
                'document' => $this->tag($block, 'CHECKNUM') ?: $this->tag($block, 'REFNUM'),
                'memo' => $this->tag($block, 'MEMO') ?: $this->tag($block, 'NAME'),
                'status' => 'pending',
            ];
        })->sortBy([['occurred_at', 'asc'], ['fit_id', 'asc']])->values();
        if ($rows->pluck('fit_id')->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages(['ofx' => 'O arquivo possui identificadores FITID repetidos.']);
        }
        $minimumDate = Carbon::parse(BankMovementService::CONTROL_START)->startOfDay();
        if ($rows->contains(fn (array $row): bool => $row['occurred_at']->lt($minimumDate)
            || ($account->opening_balance_date && $row['occurred_at']->lte($account->opening_balance_date->copy()->endOfDay())))) {
            throw ValidationException::withMessages(['ofx' => 'O arquivo contém lançamentos anteriores ao início do controle ou ao saldo inicial desta conta.']);
        }
        $ledgerBalance = filled($this->tag($text, 'BALAMT')) ? $this->money($this->tag($text, 'BALAMT')) : null;
        $ledgerAt = $this->nullableDate($this->tag($text, 'DTASOF')) ?? $rows->max('occurred_at');
        if ($ledgerBalance !== null) {
            $running = $ledgerBalance;
            $withBalances = [];
            foreach ($rows->reverse() as $index => $row) {
                $row['bank_balance'] = $running;
                $withBalances[$index] = $row;
                $running = bcsub($running, $row['amount'], 2);
            }
            $rows = collect($withBalances)->sortKeys()->values();
        }

        return DB::transaction(function () use ($account, $filename, $userId, $hash, $rows, $ledgerBalance, $ledgerAt, $bankId, $accountNumber) {
            $import = BankStatementImport::create([
                'bank_account_id' => $account->id, 'filename' => basename($filename), 'file_hash' => $hash,
                'period_start' => $rows->min('occurred_at'), 'period_end' => $rows->max('occurred_at'),
                'ledger_balance' => $ledgerBalance, 'ledger_balance_at' => $ledgerBalance !== null ? $ledgerAt : null,
                'bank_id' => $bankId, 'account_number' => $accountNumber, 'created_by' => $userId,
            ]);
            foreach ($rows as $row) {
                $import->entries()->firstOrCreate(['bank_account_id' => $account->id, 'fit_id' => $row['fit_id']], $row);
            }

            return $import;
        });
    }

    private function tag(string $text, string $name): ?string
    {
        return preg_match('/<'.preg_quote($name, '/').'>\s*([^<\r\n]+)/i', $text, $match)
            ? trim(html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) : null;
    }

    private function money(?string $value): string
    {
        $value = trim((string) $value);
        if (! preg_match('/^-?\d+(?:[.,]\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages(['ofx' => 'O arquivo contém um valor monetário inválido.']);
        }

        return bcadd(str_replace(',', '.', $value), '0', 2);
    }

    private function date(?string $value, string $label): Carbon
    {
        $date = $this->nullableDate($value);
        if (! $date) {
            throw ValidationException::withMessages(['ofx' => "O arquivo contém {$label} inválida."]);
        }

        return $date;
    }

    private function nullableDate(?string $value): ?Carbon
    {
        $date = substr((string) $value, 0, 8);
        if (! preg_match('/^\d{8}$/D', $date) || $date === '00000000') {
            return null;
        }
        try {
            return Carbon::createFromFormat('!Ymd', $date)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function validateAccount(BankAccount $account, ?string $bankId, ?string $accountNumber): void
    {
        $digits = fn (?string $value): string => ltrim((string) preg_replace('/\D/', '', (string) $value), '0');
        if ($bankId && $digits($bankId) !== $digits($account->banco)) {
            throw ValidationException::withMessages(['ofx' => 'O banco informado no OFX não corresponde à conta selecionada.']);
        }
        if ($accountNumber) {
            $ofxAccount = $digits($accountNumber);
            $registered = $digits($account->conta);
            $registeredWithDigit = $digits($account->conta.$account->conta_dv);
            if (! in_array($ofxAccount, array_unique([$registered, $registeredWithDigit]), true)) {
                throw ValidationException::withMessages(['ofx' => 'O número da conta no OFX não corresponde à conta selecionada.']);
            }
        }
    }
}
