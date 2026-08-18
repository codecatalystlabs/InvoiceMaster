<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Support\LeaveService;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index()
    {
        if (auth()->user()->seesOnlyOwnRecords() || ! can_module('leave.review')) {
            return $this->mine();
        }
        $pending = LeaveRequest::with(['employee', 'type'])->where('status', 'pending')->latest()->get();
        $recent = LeaveRequest::with(['employee', 'type'])->latest()->paginate(30);

        return view('leave.index', compact('pending', 'recent'));
    }

    public function mine()
    {
        $employee = Employee::where('user_id', auth()->id())->first();
        $types = LeaveType::where('is_active', true)->orderBy('name')->get();
        $requests = $employee
            ? LeaveRequest::with('type')->where('employee_id', $employee->id)->latest()->get()
            : collect();
        $balances = $employee ? $employee->leaveBalances()->with('type')->where('year', now()->year)->get() : collect();

        return view('leave.mine', compact('employee', 'types', 'requests', 'balances'));
    }

    public function store(Request $request)
    {
        $employee = Employee::where('user_id', auth()->id())->first()
            ?: (can_module('leave.review') ? Employee::findOrFail($request->integer('employee_id')) : null);
        abort_unless($employee, 403, 'Link a login user to an employee record first.');
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'reason' => 'nullable|string|max:500',
        ]);
        LeaveService::request($employee, $data, auth()->id());

        return back()->with('success', 'Leave request submitted.');
    }

    public function approve(Request $request, LeaveRequest $leave)
    {
        abort_unless(can_module('leave.review'), 403);
        LeaveService::approve($leave, auth()->id(), $request->input('review_notes'));

        return back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveRequest $leave)
    {
        abort_unless(can_module('leave.review'), 403);
        LeaveService::reject($leave, auth()->id(), $request->input('review_notes'));

        return back()->with('success', 'Leave rejected.');
    }
}
