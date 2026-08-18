<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\LedgerEntry;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $accountId = (int) $request->get('account_id');

        $query = LedgerEntry::with('account')
            ->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))
            ->when($accountId, fn ($q) => $q->where('account_id', $accountId));

        $debit = (float) (clone $query)->where('entry_type', 'Debit')->sum('amount');
        $credit = (float) (clone $query)->where('entry_type', 'Credit')->sum('amount');

        $entries = $query->orderByDesc('entry_date')->orderByDesc('id')->paginate(40)->withQueryString();
        $accounts = ChartOfAccount::where('is_active', true)->orderBy('account_code')->get();

        return view('ledger.index', compact('entries', 'debit', 'credit', 'from', 'to', 'accountId', 'accounts'));
    }
}
