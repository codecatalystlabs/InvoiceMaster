<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string|max:80',
        ]);

        $user = User::where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], $user->password) || $user->status !== 'Active') {
            return response()->json(['message' => 'Invalid credentials.'], 422);
        }

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;
        Audit::log('ApiLogin', 'User', $user->id, 'Mobile token issued');

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user()->load('department', 'company'))]);
    }

    protected function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'department' => $user->department ? [
                'id' => $user->department->id,
                'name' => $user->department->name,
                'code' => $user->department->code,
            ] : null,
            'company' => $user->company ? [
                'id' => $user->company->id,
                'name' => $user->company->name,
                'currency' => $user->company->currency,
            ] : null,
            'modules' => $user->allowedModules(),
        ];
    }
}
