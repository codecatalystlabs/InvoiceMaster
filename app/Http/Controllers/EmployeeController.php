<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Division;
use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use App\Support\Audit;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['user', 'department', 'division', 'position'])->orderBy('name')->paginate(30);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.form', $this->formData(new Employee([
            'status' => 'Active',
            'pay_method' => 'bank',
            'employment_type' => 'permanent',
            'start_date' => now(),
        ])));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['number'] = DocumentNumber::next('EMP', 'employees', 'number', auth()->user()->company_id);
        $employee = Employee::create($data);
        LeaveType::seedDefaults((int) $employee->company_id);
        LeaveType::seedBalanceFor($employee);
        Audit::log('Create', 'Employee', $employee->id, $employee->name);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee added.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['user', 'department', 'division', 'position', 'supervisor']);
        $balances = $employee->leaveBalances()->with('type')->where('year', now()->year)->get();
        $requests = $employee->leaveRequests()->with('type')->latest()->limit(12)->get();
        $days = $employee->attendanceDays()->where('work_date', '>=', now()->startOfMonth())->orderByDesc('work_date')->get();

        return view('employees.show', compact('employee', 'balances', 'requests', 'days'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', $this->formData($employee));
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request, $employee));
        LeaveType::seedBalanceFor($employee);

        return redirect()->route('employees.show', $employee)->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Employee removed.');
    }

    protected function formData(Employee $employee): array
    {
        return [
            'employee' => $employee,
            'users' => User::orderBy('name')->get(),
            'departments' => Department::orderBy('sort_order')->orderBy('name')->get(),
            'divisions' => Division::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'positions' => Position::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'supervisors' => Employee::where('status', 'Active')
                ->when($employee->exists, fn ($q) => $q->where('id', '!=', $employee->id))
                ->orderBy('name')
                ->get(),
        ];
    }

    protected function validated(Request $request, ?Employee $employee = null): array
    {
        $id = $employee?->id;
        $companyId = auth()->user()->company_id;

        $data = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'division_id' => 'nullable|exists:divisions,id',
            'position_id' => 'nullable|exists:positions,id',
            'supervisor_id' => 'nullable|exists:employees,id',
            'name' => 'required|string|max:150',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'national_id' => 'nullable|string|max:40',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:40',
            'address' => 'nullable|string|max:255',
            'tin' => 'nullable|string|max:40',
            'nssf_number' => 'nullable|string|max:40',
            'next_of_kin' => 'nullable|string|max:150',
            'next_of_kin_phone' => 'nullable|string|max:40',
            'job_title' => 'nullable|string|max:100',
            'employment_type' => 'required|in:permanent,contract,intern,casual',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'pay_method' => 'required|in:bank,mtn_momo,airtel_money,cash',
            'bank_name' => 'nullable|string|max:80',
            'pay_account' => 'nullable|string|max:80',
            'machine_pin' => 'nullable|string|max:40|unique:employees,machine_pin,'.($id ?: 'NULL').',id,company_id,'.$companyId,
            'status' => 'required|in:Active,Inactive',
        ]);
        foreach (['user_id', 'department_id', 'division_id', 'position_id', 'supervisor_id', 'gender', 'machine_pin', 'bank_name'] as $key) {
            if (($data[$key] ?? '') === '') {
                $data[$key] = null;
            }
        }
        $data['allowances'] = (float) ($data['allowances'] ?? 0);
        if (! empty($data['position_id'])) {
            $position = Position::find($data['position_id']);
            if ($position) {
                $data['job_title'] = $data['job_title'] ?: $position->name;
                $data['department_id'] = $data['department_id'] ?: $position->department_id;
                $data['division_id'] = $data['division_id'] ?: $position->division_id;
            }
        }
        if (! empty($data['division_id'])) {
            $division = Division::find($data['division_id']);
            if ($division && $data['department_id'] && (int) $division->department_id !== (int) $data['department_id']) {
                $data['division_id'] = null;
            }
        }

        return $data;
    }
}
