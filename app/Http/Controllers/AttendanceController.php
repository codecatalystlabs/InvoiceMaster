<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDay;
use App\Models\AttendancePunch;
use App\Models\Employee;
use App\Support\AttendanceService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $employeeId = $request->get('employee_id');
        $own = auth()->user()->seesOnlyOwnRecords();
        $query = AttendanceDay::with('employee')->whereBetween('work_date', [$from, $to])->orderByDesc('work_date');
        if ($own) {
            $query->whereHas('employee', fn ($q) => $q->where('user_id', auth()->id()));
        } elseif ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        $days = $query->paginate(40)->withQueryString();
        $employees = $own ? collect() : Employee::orderBy('name')->get();

        return view('attendance.index', compact('days', 'from', 'to', 'employees', 'employeeId'));
    }

    public function punches(Request $request)
    {
        abort_if(auth()->user()->seesOnlyOwnRecords(), 403);
        $from = $request->get('from', now()->toDateString());
        $to = $request->get('to', now()->toDateString());
        $punches = AttendancePunch::with(['employee', 'device'])
            ->whereBetween('punched_at', [$from.' 00:00:00', $to.' 23:59:59'])
            ->orderByDesc('punched_at')
            ->paginate(50)
            ->withQueryString();

        return view('attendance.punches', compact('punches', 'from', 'to'));
    }

    public function rebuild(Request $request)
    {
        abort_unless(can_module('hr') || can_module('leave.review'), 403);
        $data = $request->validate([
            'from' => 'required|date',
            'to' => 'required|date|after_or_equal:from',
        ]);
        $n = AttendanceService::rebuildRange(auth()->user()->company_id, $data['from'], $data['to']);

        return back()->with('success', 'Rebuilt '.$n.' attendance days.');
    }
}
