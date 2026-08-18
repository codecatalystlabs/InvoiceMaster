<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Support\Audit;
use App\Support\CashBookService;
use App\Support\DocumentNumber;
use App\Support\LedgerService;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $expenses = Expense::with('account')
            ->when($request->q, fn ($q) => $q->where('vendor_name', 'like', '%'.$request->q.'%')->orWhere('expense_number', 'like', '%'.$request->q.'%'))
            ->when($request->category, fn ($q) => $q->where('category', $request->category))
            ->when($request->status, fn ($q) => $q->where('payment_status', $request->status))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        $total = (float) Expense::sum('amount');
        $categories = Expense::select('category')->distinct()->pluck('category');

        return view('expenses.index', compact('expenses', 'total', 'categories'));
    }

    public function create()
    {
        return view('expenses.form', [
            'expense' => new Expense(['expense_date' => now(), 'payment_status' => 'Pending', 'payment_method' => 'Cash']),
            'accounts' => ChartOfAccount::where('account_type', 'Expense')->orderBy('account_name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['expense_number'] = DocumentNumber::next('EXP', 'expenses', 'expense_number', auth()->user()->company_id);
        $expense = Expense::create($data);
        $this->maybePostCash($expense);
        LedgerService::postExpense($expense);
        Audit::log('Create', 'Expense', $expense->id, $expense->expense_number);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense)
    {
        $expense->load('account');

        return view('expenses.show', compact('expense'));
    }

    public function edit(Expense $expense)
    {
        return view('expenses.form', [
            'expense' => $expense,
            'accounts' => ChartOfAccount::where('account_type', 'Expense')->orderBy('account_name')->get(),
        ]);
    }

    public function update(Request $request, Expense $expense)
    {
        $expense->update($this->validated($request));
        LedgerService::postExpense($expense);
        Audit::log('Update', 'Expense', $expense->id, $expense->expense_number);

        return redirect()->route('expenses.show', $expense)->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        LedgerService::forget('Expense', $expense->id);
        Audit::log('Delete', 'Expense', $expense->id, $expense->expense_number);
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    protected function maybePostCash(Expense $expense): void
    {
        if ($expense->payment_status !== 'Paid') {
            return;
        }
        CashBookService::record([
            'entry_date' => $expense->expense_date,
            'description' => 'Expense '.$expense->expense_number.' — '.$expense->vendor_name,
            'type' => 'credit',
            'amount' => $expense->amount,
            'account_id' => $expense->account_id,
            'payment_method' => $expense->payment_method,
            'expense_id' => $expense->id,
        ]);
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'expense_date' => 'required|date',
            'account_id' => 'nullable|exists:chart_of_accounts,id',
            'vendor_name' => 'required|string|max:100',
            'category' => 'required|string|max:100',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'payment_status' => 'required|string',
            'is_recurring' => 'nullable|boolean',
            'recurrence_frequency' => 'nullable|string',
            'next_recurrence_date' => 'nullable|date',
            'description' => 'nullable|string',
        ]);
        $data['is_recurring'] = $request->boolean('is_recurring');

        return $data;
    }
}
