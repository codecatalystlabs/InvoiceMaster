<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use App\Support\DocumentExport;
use App\Support\LedgerService;
use App\Support\LedgerSheet;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $accountId = (int) $request->get('account_id');

        $query = $this->filteredQuery($from, $to, $accountId);

        $debit = (float) (clone $query)->where('entry_type', 'Debit')->sum('amount');
        $credit = (float) (clone $query)->where('entry_type', 'Credit')->sum('amount');

        $entries = $query->orderByDesc('entry_date')->orderByDesc('id')->paginate(40)->withQueryString();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('account_code')->get();

        return view('ledger.index', compact('entries', 'debit', 'credit', 'from', 'to', 'accountId', 'accounts'));
    }

    public function rebuild()
    {
        $count = LedgerService::rebuild(auth()->user()->company_id);

        return redirect()->route('ledger.index')->with('success', 'Ledger rebuilt: '.$count.' lines from invoices, receipts, expenses, and cash book.');
    }

    public function preview(Request $request)
    {
        $data = $this->sheetData($request);

        return view('ledger.preview', $data);
    }

    public function pdf(Request $request)
    {
        $data = $this->sheetData($request);
        $filename = $this->pdfFilename($data['from'], $data['to'], $data['account']);

        return DocumentExport::sendPdf(
            DocumentExport::ledgerPdf($data['pages'], $data['company'], $data['title']),
            $filename,
            $request->boolean('inline')
        );
    }

    protected function sheetData(Request $request): array
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $accountId = (int) $request->get('account_id');
        $account = $accountId ? ChartOfAccount::find($accountId) : null;
        $company = auth()->user()->company;

        $entries = $this->filteredQuery($from, $to, $accountId)
            ->with('account')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->limit(500)
            ->get();

        $title = $account ? strtoupper($account->account_name) : 'LEDGER';
        $pages = LedgerSheet::pages($entries, $company, $account === null);

        return compact('from', 'to', 'accountId', 'account', 'company', 'pages', 'title', 'entries');
    }

    protected function filteredQuery(?string $from, ?string $to, int $accountId)
    {
        return LedgerEntry::with('account')
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId));
    }

    protected function pdfFilename(?string $from, ?string $to, $account): string
    {
        $parts = ['ledger'];
        if ($account) {
            $parts[] = $account->account_code;
        }
        if ($from) {
            $parts[] = $from;
        }
        if ($to) {
            $parts[] = $to;
        }

        return implode('-', $parts).'.pdf';
    }
}
