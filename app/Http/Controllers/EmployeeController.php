<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Support\Audit;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = Employee::with(['user', 'department'])->orderBy('name')->paginate(30);

        return view('employees.index', compact('employees'));
    }

    public function create()
    {
        return view('employees.form', [
            'employee' => new Employee(['status' => 'Active', 'pay_method' => 'bank', 'start_date' => now()]),
            'users' => User::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['number'] = DocumentNumber::next('EMP', 'employees', 'number', auth()->user()->company_id);
        $employee = Employee::create($data);
        Audit::log('Create', 'Employee', $employee->id, $employee->name);

        return redirect()->route('employees.index')->with('success', 'Employee added.');
    }

    public function edit(Employee $employee)
    {
        return view('employees.form', [
            'employee' => $employee,
            'users' => User::orderBy('name')->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $employee->update($this->validated($request));

        return redirect()->route('employees.index')->with('success', 'Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return back()->with('success', 'Employee removed.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'department_id' => 'nullable|exists:departments,id',
            'name' => 'required|string|max:150',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:40',
            'tin' => 'nullable|string|max:40',
            'nssf_number' => 'nullable|string|max:40',
            'job_title' => 'nullable|string|max:100',
            'start_date' => 'nullable|date',
            'basic_salary' => 'required|numeric|min:0',
            'allowances' => 'nullable|numeric|min:0',
            'pay_method' => 'required|in:bank,mtn_momo,airtel_money,cash',
            'pay_account' => 'nullable|string|max:80',
            'status' => 'required|in:Active,Inactive',
        ]);
    }
}
