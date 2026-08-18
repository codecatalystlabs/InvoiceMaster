<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index()
    {
        $departments = Department::with('head')->withCount('users')->orderBy('name')->get();
        $users = User::where('company_id', auth()->user()->company_id)->orderBy('name')->get();

        return view('departments.index', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'code' => 'nullable|string|max:20',
            'head_user_id' => 'nullable|exists:users,id',
        ]);
        $dept = Department::create($data + ['is_active' => true]);
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
}
