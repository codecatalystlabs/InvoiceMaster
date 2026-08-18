<?php

namespace App\Http\Controllers;

use App\Models\AttendanceDay;
use App\Models\Employee;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class HrController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $headcount = Employee::where('status', 'Active')->count();
        $days = AttendanceDay::with('employee')
            ->whereDate('work_date', $date)
            ->get();
        $onLeave = LeaveRequest::with(['employee', 'type'])
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get();
        $pendingLeave = LeaveRequest::where('status', 'pending')->count();

        return view('hr.index', compact('date', 'headcount', 'days', 'onLeave', 'pendingLeave'));
    }
}
