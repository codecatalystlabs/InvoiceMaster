<?php

namespace App\Http\Controllers;

use App\Models\AnnualBudget;
use App\Models\BudgetAllocation;
use App\Models\Department;
use App\Support\Audit;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $budgets = AnnualBudget::with(['department', 'allocations'])
            ->when($request->year, fn ($q) => $q->where('year', $year), fn ($q) => $q->where('year', $year))
            ->orderBy('year', 'desc')
            ->get();
        $departments = Department::where('is_active', true)->orderBy('name')->get();

        return view('budgets.index', compact('budgets', 'departments', 'year'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'year' => 'required|integer|min:2020',
            'title' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $budget = AnnualBudget::create($data + ['status' => 'draft']);
        Audit::log('Create', 'Budget', $budget->id, $budget->title, $budget->amount, ['module' => 'budgets']);

        return redirect()->route('budgets.show', $budget)->with('success', 'Budget drafted.');
    }

    public function show(AnnualBudget $budget)
    {
        $budget->load(['department', 'allocations', 'approver']);

        return view('budgets.show', compact('budget'));
    }

    public function approve(AnnualBudget $budget)
    {
        abort_unless($budget->status === 'draft', 403);
        $budget->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        Audit::log('Approve', 'Budget', $budget->id, $budget->title, $budget->amount, ['module' => 'budgets']);

        return back()->with('success', 'Budget approved. You can now allocate it.');
    }

    public function allocate(Request $request, AnnualBudget $budget)
    {
        abort_unless($budget->status === 'approved', 403);
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'category' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ]);
        if ($data['amount'] - $budget->remainingToAllocate() > 0.009) {
            return back()->with('error', 'Allocation exceeds remaining budget ('.money($budget->remainingToAllocate()).').');
        }
        $line = $budget->allocations()->create($data);
        Audit::log('Allocate', 'Budget', $budget->id, $line->name.' · '.money($line->amount), $line->amount, ['module' => 'budgets']);

        return back()->with('success', 'Allocation added.');
    }

    public function destroyAllocation(BudgetAllocation $allocation)
    {
        abort_unless($allocation->budget->status === 'approved', 403);
        abort_if($allocation->requisitions()->exists(), 403, 'This allocation already has requisitions.');
        $allocation->delete();

        return back()->with('success', 'Allocation removed.');
    }
}
