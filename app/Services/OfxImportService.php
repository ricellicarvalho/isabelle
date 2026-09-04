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
        $hash = hash('sha256', $contents);
        if (BankStatementImport::where('bank_account_id', $account->id)->where('file_hash', $hash)->exists()) {
            throw ValidationException::withMessages(['ofx' => 'Este arquivo OFX já foi importado nesta conta.']);
        }
        preg_match_all('/<STMTTRN>(.*?)(?:<\/STMTTRN>|(?=<STMTTRN>))/si', $contents, $matches);
        if (empty($matches[1])) throw ValidationException::withMessages(['ofx' => 'O arquivo não contém movimentações OFX reconhecíveis.']);

        return DB::transaction(function () use ($account, $contents, $filename, $userId, $hash, $matches) {
            $rows = collect($matches[1])->map(function (string $block): array {
                $tag = fn (string $name): ?string => preg_match('/<'.$name.'>([^<\r\n]+)/i', $block, $m) ? trim(html_entity_decode($m[1])) : null;
                $rawDate = $tag('DTPOSTED');
                return ['fit_id' => $tag('FITID') ?: hash('sha256', $block), 'occurred_at' => Carbon::createFromFormat('Ymd', substr((string) $rawDate, 0, 8))->startOfDay(), 'amount' => (float) str_replace(',', '.', (string) $tag('TRNAMT')), 'type' => $tag('TRNTYPE'), 'document' => $tag('CHECKNUM') ?: $tag('REFNUM'), 'memo' => $tag('MEMO') ?: $tag('NAME'), 'status' => 'pending'];
            });
            $import = BankStatementImport::create(['bank_account_id' => $account->id, 'filename' => $filename, 'file_hash' => $hash, 'period_start' => $rows->min('occurred_at'), 'period_end' => $rows->max('occurred_at'), 'created_by' => $userId]);
            foreach ($rows as $row) $import->entries()->firstOrCreate(['bank_account_id' => $account->id, 'fit_id' => $row['fit_id']], $row);
            return $import;
        });
    }
}
