<?php

namespace App\Http\Controllers;

use App\Services\BankAccountReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BankAccountReportController extends Controller
{
    public function __invoke(Request $request)
    {
        Gate::authorize('View:BankAccountReport');
        $report = BankAccountReportService::generate($request->only(['bank_account_id', 'data_inicio', 'data_fim', 'category_id', 'reconciliation', 'search']));
        $pdf = Pdf::loadView('pdf.bank-account-report', compact('report'))->setPaper('a4', 'landscape');

        return response($pdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="movimentacao-conta-'.$report['account']['id'].'.pdf"']);
    }
}
