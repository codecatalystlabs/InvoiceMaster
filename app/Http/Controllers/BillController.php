<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\ChartOfAccount;
use App\Models\Project;
use App\Support\BillService;
use Illuminate\Http\Request;

class BillController extends Controller
{
    public function index()
    {
        $bills = Bill::latest('bill_date')->paginate(20);

        return view('bills.index', compact('bills'));
    }

    public function create()
    {
        return view('bills.form', [
            'bill' => new Bill(['bill_date' => now(), 'due_date' => now()->addDays(14), 'status' => 'Open']),
            'accounts' => ChartOfAccount::where('account_type', 'Expense')->orderBy('account_code')->get(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $bill = new Bill($data + ['company_id' => auth()->user()->company_id, 'created_by' => auth()->id()]);
        BillService::save($bill, $bill->getAttributes(), $request->input('items', []));

        return redirect()->route('bills.show', $bill)->with('success', 'Bill recorded.');
    }

    public function show(Bill $bill)
    {
        $bill->load(['items', 'account', 'project']);

        return view('bills.show', compact('bill'));
    }

    public function pay(Request $request, Bill $bill)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,bank,mtn_momo,airtel_money',
        ]);
        BillService::pay($bill, (float) $data['amount'], $data['method']);

        return back()->with('success', 'Bill payment recorded.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'vendor_name' => 'required|string|max:150',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date',
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'project_id' => 'nullable|exists:projects,id',
            'tax' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
    }
}
