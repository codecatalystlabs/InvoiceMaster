<?php

namespace App\Http\Controllers;

use App\Models\BudgetAllocation;
use App\Models\Department;
use App\Models\PettyCashFund;
use App\Models\User;
use App\Support\PettyCashService;
use Illuminate\Http\Request;

class PettyCashController extends Controller
{
    public function index()
    {
        $funds = PettyCashFund::with(['department', 'custodian'])->orderBy('name')->get();

        return view('petty-cash.index', compact('funds'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'department_id' => 'nullable|exists:departments,id',
            'custodian_user_id' => 'nullable|exists:users,id',
            'float_limit' => 'required|numeric|min:0',
        ]);
        $fund = PettyCashFund::create($data + ['balance' => 0, 'is_active' => true]);

        return redirect()->route('petty-cash.show', $fund)->with('success', 'Petty cash fund created.');
    }

    public function show(PettyCashFund $pettyCashFund)
    {
        $fund = $pettyCashFund->load(['department', 'custodian']);
        $entries = $fund->entries()->latest('id')->paginate(25);
        $departments = Department::where('is_active', true)->orderBy('name')->get();
        $users = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();
        $allocations = BudgetAllocation::with('budget.department')
            ->where('category', 'petty_cash')
            ->whereHas('budget', fn ($q) => $q->where('status', 'approved'))
            ->get();

        return view('petty-cash.show', compact('fund', 'entries', 'departments', 'users', 'allocations'));
    }

    public function topup(Request $request, PettyCashFund $pettyCashFund)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:200',
            'type' => 'required|in:allocation,replenish',
        ]);
        PettyCashService::post($pettyCashFund, $data['type'], (float) $data['amount'], $data['description']);

        return back()->with('success', 'Fund topped up.');
    }

    public function createForm()
    {
        return view('petty-cash.create', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'users' => User::where('company_id', auth()->user()->company_id)->orderBy('name')->get(),
        ]);
    }
}
