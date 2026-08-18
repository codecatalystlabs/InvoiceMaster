<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Division;
use App\Models\Position;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('head')
            ->withCount(['users', 'employees', 'divisions', 'positions'])
            ->orderByRaw('CASE WHEN sort_order = 0 THEN 1 ELSE 0 END')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $users = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('departments.index', compact('departments', 'users'));
    }

    public function show(Department $department)
    {
        $department->load(['head', 'divisions', 'positions.division'])->loadCount('employees');
        $users = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('departments.show', compact('department', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:20',
            'head_user_id' => 'nullable|exists:users,id',
        ]);
        $dept = Department::create($data + ['is_active' => true, 'sort_order' => 100]);
        Audit::log('Create', 'Department', $dept->id, $dept->name, null, ['module' => 'departments']);

        return back()->with('success', 'Department created.');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:20',
            'head_user_id' => 'nullable|exists:users,id',
            'is_active' => 'required|boolean',
        ]);
        $department->update($data);
        Audit::log('Update', 'Department', $department->id, $department->name, null, ['module' => 'departments']);

        return back()->with('success', 'Department updated.');
    }

    public function storeDivision(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:20',
        ]);
        $data['code'] = strtoupper($data['code']);
        Division::create($data + [
            'department_id' => $department->id,
            'is_active' => true,
            'sort_order' => $department->divisions()->count() * 10 + 10,
        ]);

        return redirect()->route('departments.show', $department)->with('success', 'Division added to '.$department->name.'.');
    }

    public function storePosition(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'required|string|max:20',
            'level' => 'required|in:intern,junior,mid,senior,lead,manager,director,executive',
            'division_id' => 'nullable|exists:divisions,id',
        ]);
        $data['code'] = strtoupper($data['code']);
        Position::create($data + [
            'department_id' => $department->id,
            'is_active' => true,
            'sort_order' => $department->positions()->count() * 10 + 10,
        ]);

        return redirect()->route('departments.show', $department)->with('success', 'Position added to '.$department->name.'.');
    }
}
