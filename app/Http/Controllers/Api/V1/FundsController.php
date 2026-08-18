<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AnnualBudget;
use App\Models\Department;
use App\Models\PettyCashFund;
use App\Models\Requisition;
use App\Support\PettyCashService;
use App\Support\RequisitionService;
use Illuminate\Http\Request;

class FundsController extends Controller
{
    public function departments()
    {
        abort_unless(auth()->user()->canAccess('departments') || auth()->user()->canAccess('requisitions'), 403);

        return response()->json(['data' => Department::where('is_active', true)->orderBy('name')->get(['id', 'name', 'code'])]);
    }

    public function budgets(Request $request)
    {
        abort_unless(auth()->user()->canAccess('budgets'), 403);
        $year = (int) $request->get('year', now()->year);
        $rows = AnnualBudget::with('department', 'allocations')->where('year', $year)->get();

        return response()->json(['year' => $year, 'data' => $rows]);
    }

    public function requisitions(Request $request)
    {
        abort_unless(auth()->user()->canAccess('requisitions'), 403);
        $user = auth()->user();
        $rows = Requisition::with('department', 'requester')
            ->when($user->seesOnlyOwnRecords(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20);

        return response()->json($rows);
    }

    public function storeRequisition(Request $request)
    {
        abort_unless(auth()->user()->canAccess('requisitions'), 403);
        $data = $request->validate([
            'title' => 'required|string|max:150',
            'purpose' => 'nullable|string',
            'amount' => 'required|numeric|min:0.01',
            'department_id' => 'nullable|exists:departments,id',
            'budget_allocation_id' => 'nullable|exists:budget_allocations,id',
            'petty_cash_fund_id' => 'nullable|exists:petty_cash_funds,id',
            'type' => 'nullable|in:petty_cash,general',
        ]);
        $req = RequisitionService::submit(auth()->user(), $data);

        return response()->json(['requisition' => $req], 201);
    }

    public function showRequisition(Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions'), 403);
        if (auth()->user()->seesOnlyOwnRecords()) {
            abort_unless($requisition->user_id === auth()->id(), 403);
        }

        return response()->json(['requisition' => $requisition->load('department', 'allocation', 'fund', 'lines', 'steps.user', 'requester')]);
    }

    public function initiate(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);

        return response()->json(['requisition' => RequisitionService::initiate($requisition, auth()->user(), $request->input('notes'))]);
    }

    public function approve(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);

        return response()->json(['requisition' => RequisitionService::approve($requisition, auth()->user(), $request->input('notes'))]);
    }

    public function reject(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);
        $data = $request->validate(['reason' => 'required|string|max:500']);

        return response()->json(['requisition' => RequisitionService::reject($requisition, auth()->user(), $data['reason'])]);
    }

    public function disburse(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('petty-cash') || auth()->user()->canAccess('requisitions.review'), 403);
        $data = $request->validate([
            'petty_cash_fund_id' => 'nullable|exists:petty_cash_funds,id',
            'disbursement_method' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);
        if (! empty($data['petty_cash_fund_id'])) {
            $requisition->update(['petty_cash_fund_id' => $data['petty_cash_fund_id']]);
        }

        return response()->json(['requisition' => RequisitionService::disburse($requisition->fresh(), auth()->user(), $data)]);
    }

    public function account(Request $request, Requisition $requisition)
    {
        abort_unless($requisition->user_id === auth()->id() || auth()->user()->canAccess('requisitions.review'), 403);
        $data = $request->validate([
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.description' => 'required|string',
            'lines.*.amount' => 'required|numeric|min:0.01',
            'lines.*.spent_on' => 'nullable|date',
        ]);

        return response()->json(['requisition' => RequisitionService::account($requisition, auth()->user(), $data['lines'], $data['notes'] ?? null)]);
    }

    public function close(Request $request, Requisition $requisition)
    {
        abort_unless(auth()->user()->canAccess('requisitions.review'), 403);

        return response()->json(['requisition' => RequisitionService::close($requisition, auth()->user(), $request->input('notes'))]);
    }

    public function pettyCash()
    {
        abort_unless(auth()->user()->canAccess('petty-cash') || auth()->user()->canAccess('requisitions'), 403);
        $funds = PettyCashFund::with('department')->where('is_active', true)->get();

        return response()->json(['data' => $funds]);
    }

    public function pettyCashShow(PettyCashFund $pettyCashFund)
    {
        abort_unless(auth()->user()->canAccess('petty-cash'), 403);

        return response()->json([
            'fund' => $pettyCashFund->load('department', 'custodian'),
            'entries' => $pettyCashFund->entries()->latest('id')->limit(50)->get(),
        ]);
    }

    public function pettyCashTopup(Request $request, PettyCashFund $pettyCashFund)
    {
        abort_unless(auth()->user()->canAccess('petty-cash'), 403);
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string',
            'type' => 'required|in:allocation,replenish',
        ]);
        $entry = PettyCashService::post($pettyCashFund, $data['type'], (float) $data['amount'], $data['description']);

        return response()->json(['entry' => $entry, 'balance' => $pettyCashFund->fresh()->balance], 201);
    }
}
