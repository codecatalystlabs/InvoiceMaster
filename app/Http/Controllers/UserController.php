<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('department')->where('company_id', auth()->user()->company_id)->orderBy('name')->paginate(20);
        $roles = role_options();
        $catalog = config('modules.catalog');
        $departments = \App\Models\Department::where('is_active', true)->orderBy('name')->get();

        return view('users.index', compact('users', 'roles', 'catalog', 'departments'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['company_id'] = auth()->user()->company_id;
        $data['status'] = 'Active';
        $user = User::create($data);
        Audit::log('Create', 'User', $user->id, $user->email.' · '.$user->role, null, ['module' => 'users']);

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->company_id === auth()->user()->company_id, 403);
        $data = $this->validated($request, $user->id);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);
        Audit::log('Update', 'User', $user->id, $user->email.' · '.$user->role, null, ['module' => 'users']);

        return back()->with('success', 'User updated.');
    }

    public function destroy(User $user)
    {
        abort_unless($user->company_id === auth()->user()->company_id, 403);
        abort_if($user->id === auth()->id(), 403, 'You cannot delete your own account.');
        Audit::log('Delete', 'User', $user->id, $user->email, null, ['module' => 'users']);
        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    protected function validated(Request $request, ?int $userId = null): array
    {
        $data = $request->validate([
            'name' => 'required|string',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],
            'password' => $userId ? 'nullable|min:8' : 'required|min:8',
            'role' => ['required', Rule::in(role_options())],
            'department_id' => 'nullable|exists:departments,id',
            'status' => $userId ? 'required|in:Active,Inactive' : 'nullable',
            'must_declare_meals' => 'nullable|boolean',
            'modules' => 'nullable|array',
            'modules.*' => 'string',
        ]);
        $data['must_declare_meals'] = false;
        $data['modules'] = array_values(array_filter($data['modules'] ?? []));
        if ($data['modules'] === []) {
            $data['modules'] = null;
        }

        return $data;
    }
}
