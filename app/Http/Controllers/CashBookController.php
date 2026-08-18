<?php

namespace App\Http\Controllers;

use App\Models\CashBookEntry;
use App\Models\ChartOfAccount;
use App\Support\Audit;
use App\Support\CashBookService;
use App\Support\DocumentExport;
use Illuminate\Http\Request;

class CashBookController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');
        $q = $request->get('q');
        $entries = CashBookEntry::with('account')
            ->when($from, fn ($query) => $query->whereDate('entry_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('entry_date', '<=', $to))
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('description', 'like', "%$q%")->orWhere('number', 'like', "%$q%");
            }))
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $balance = (float) (CashBookEntry::orderByDesc('id')->value('balance_after') ?? 0);
        $in = (float) CashBookEntry::where('type', 'debit')->sum('amount');
        $out = (float) CashBookEntry::where('type', 'credit')->sum('amount');

        return view('cashbook.index', compact('entries', 'balance', 'in', 'out', 'from', 'to', 'q'));
    }

    public function create()
    {
        return view('cashbook.form', [
            'entry' => new CashBookEntry(['entry_date' => now(), 'type' => 'debit']),
            'accounts' => ChartOfAccount::where('is_active', true)->orderBy('account_code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $entry = CashBookService::record($data);
        Audit::log('Create', 'CashBook', $entry->id, $entry->number);

        return redirect()->route('cashbook.index')->with('success', 'Cash book entry recorded.');
    }

    public function show(CashBookEntry $cashbook)
    {
        return view('cashbook.show', ['entry' => $cashbook]);
    }

    public function pdf(Request $request, CashBookEntry $cashbook)
    {
        return DocumentExport::sendPdf(
            DocumentExport::cashbookPdf($cashbook),
            $cashbook->number.'.pdf',
            $request->boolean('inline')
        );
    }

    public function edit(CashBookEntry $cashbook)
    {
        return view('cashbook.form', [
            'entry' => $cashbook,
            'accounts' => ChartOfAccount::where('is_active', true)->orderBy('account_code')->get(),
        ]);
    }

    public function update(Request $request, CashBookEntry $cashbook)
    {
        $data = $this->validated($request);
        $cashbook->update($data);
        CashBookService::recomputeFrom(auth()->user()->company_id, $cashbook->id);
        Audit::log('Update', 'CashBook', $cashbook->id, $cashbook->number);

        return redirect()->route('cashbook.index')->with('success', 'Cash book entry updated.');
    }

    public function destroy(CashBookEntry $cashbook)
    {
        $id = $cashbook->id;
        $companyId = $cashbook->company_id;
        $cashbook->delete();
        CashBookService::recomputeFrom($companyId, $id);
        Audit::log('Delete', 'CashBook', $id, '');

        return back()->with('success', 'Entry deleted and balances recomputed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'entry_date' => 'required|date',
            'description' => 'required|string|max:255',
            'folio' => 'nullable|string|max:50',
            'discount_allowed' => 'nullable|numeric',
            'type' => 'required|in:debit,credit',
            'amount' => 'required|numeric|min:0.01',
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'payment_method' => 'nullable|string',
        ]);
    }
}
