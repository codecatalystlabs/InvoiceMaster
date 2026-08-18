<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = ChartOfAccount::orderBy('account_code')->get()->groupBy('account_type');

        return view('accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'account_code' => 'required|string|max:20',
            'account_name' => 'required|string|max:100',
            'account_type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'description' => 'nullable|string',
        ]);
        ChartOfAccount::create($data);

        return back()->with('success', 'Account created.');
    }

    public function update(Request $request, ChartOfAccount $account)
    {
        $account->update($request->validate([
            'account_code' => 'required|string|max:20',
            'account_name' => 'required|string|max:100',
            'account_type' => 'required|in:Asset,Liability,Equity,Revenue,Expense',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]) + ['is_active' => $request->boolean('is_active')]);

        return back()->with('success', 'Account updated.');
    }

    public function destroy(ChartOfAccount $account)
    {
        $account->delete();

        return back()->with('success', 'Account deleted.');
    }
}
