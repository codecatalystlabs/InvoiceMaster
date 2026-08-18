<?php

namespace App\Http\Controllers;

use App\Models\BudgetAllocation;
use App\Models\Department;
use App\Models\PettyCashFund;
use App\Models\Requisition;
use App\Support\RequisitionService;
use Illuminate\Http\Request;

class RequisitionController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $rows = Requisition::with(['requester', 'department', 'allocation'])
            ->when($user->seesOnlyOwnRecords(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('requisitions.index', compact('rows'));
    }

    public function create()
    {
        return view('requisitions.form', [
            'departments' => Department::where('is_active', true)->orderBy('name')->get(),
            'allocations' => BudgetAllocation::with('budget.department')->whereHas('budget', fn ($q) => $q->where('status', 'approved')->where('year', now()->year))->get(),
            'funds' => PettyCashFund::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'purpose' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'department_id' => 'nullable|exists:departments,id',
            'budget_allocation_id' => 'nullable|exists:budget_allocations,id',
            'petty_cash_fund_id' => 'nullable|exists:petty_cash_funds,id',
            'type' => 'required|in:petty_cash,general',
        ]);
        $req = RequisitionService::submit(auth()->user(), $data);

        return redirect()->route('requisitions.show', $req)->with('success', 'Requisition submitted.');
    }

    public function show(Requisition $requisition)
    {
        $this->authorizeReq($requisition);
        $requisition->load(['requester', 'department', 'allocation.budget', 'fund', 'steps.user', 'lines']);
        $funds = PettyCashFund::where('is_active', true)->orderBy('name')->get();

        return view('requisitions.show', compact('requisition', 'funds'));
    }

    public function initiate(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);
        RequisitionService::initiate($requisition, auth()->user(), $request->input('notes'));

        return back()->with('success', 'Requisition initiated.');
    }

    public function approve(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);
        RequisitionService::approve($requisition, auth()->user(), $request->input('notes'));

        return back()->with('success', 'Requisition approved.');
    }

    public function reject(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);
        $data = $request->validate(['reject_reason' => 'required|string|max:500']);
        RequisitionService::reject($requisition, auth()->user(), $data['reject_reason']);

        return back()->with('success', 'Requisition rejected.');
    }

    public function disburse(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('petty-cash') || auth()->user()->canAccess('requisitions.review'), 403);
        $data = $request->validate([
            'petty_cash_fund_id' => 'nullable|exists:petty_cash_funds,id',
            'disbursement_method' => 'nullable|string|max:40',
            'notes' => 'nullable|string',
        ]);
        if (! empty($data['petty_cash_fund_id'])) {
            $requisition->petty_cash_fund_id = $data['petty_cash_fund_id'];
            $requisition->save();
        }
        RequisitionService::disburse($requisition, auth()->user(), $data);

        return back()->with('success', 'Cash issued. Staff can now account for it.');
    }

    public function account(Request $request, Requisition $requisition)
    {
        $this->authorizeReq($requisition);
        abort_unless($requisition->user_id === auth()->id() || auth()->user()->canAccess('requisitions.review'), 403);
        $data = $request->validate([
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.amount' => 'required|numeric|min:0.01',
            'lines.*.spent_on' => 'nullable|date',
            'lines.*.receipt' => 'nullable|file|max:4096',
        ]);
        $lines = $data['lines'];
        foreach ($lines as $i => $line) {
            if ($request->file("lines.$i.receipt")) {
                $lines[$i]['receipt'] = $request->file("lines.$i.receipt");
            }
        }
        RequisitionService::account($requisition, auth()->user(), $lines, $data['notes'] ?? null);

        return back()->with('success', 'Accountability submitted for review.');
    }

    public function close(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);
        RequisitionService::close($requisition, auth()->user(), $request->input('notes'));

        return back()->with('success', 'Accountability accepted. Petty cash updated.');
    }

    protected function authorizeReq(Requisition $requisition): void
    {
        if (auth()->user()->seesOnlyOwnRecords()) {
            abort_unless($requisition->user_id === auth()->id(), 403);
        }
    }
}
