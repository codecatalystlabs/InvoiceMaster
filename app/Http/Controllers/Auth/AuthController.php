<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ChartOfAccount;
use App\Models\Company;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        if (Auth::user()->status !== 'Active') {
            Auth::logout();

            return back()->withErrors(['email' => 'This account is inactive.']);
        }

        Audit::log('Login', 'User', Auth::id(), 'User logged in');

        return redirect()->intended(route('dashboard'));
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'company_name' => 'required|string|max:150',
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $company = Company::create([
            'name' => $data['company_name'],
            'email' => $data['email'],
            'currency' => 'UGX',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'Admin',
            'status' => 'Active',
        ]);

        ChartOfAccount::seedDefaults($company->id);
        \App\Models\CanteenItem::seedDefaults($company->id);
        \App\Models\Department::seedDefaults($company->id);

        Auth::login($user);
        Audit::log('Register', 'User', $user->id, 'Company created');

        return redirect()->route('dashboard');
    }

    public function showAcceptInvite(string $token)
    {
        $invite = Invitation::withoutGlobalScopes()->where('token', $token)->whereNull('accepted_at')->firstOrFail();

        return view('auth.accept-invite', compact('invite'));
    }

    public function acceptInvite(Request $request, string $token)
    {
        $invite = Invitation::withoutGlobalScopes()->where('token', $token)->whereNull('accepted_at')->firstOrFail();

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'password' => 'required|min:8|confirmed',
        ]);

        $user = User::create([
            'company_id' => $invite->company_id,
            'name' => $data['name'],
            'email' => $invite->email,
            'password' => $data['password'],
            'role' => $invite->role,
            'status' => 'Active',
        ]);

        $invite->update(['accepted_at' => now()]);
        Auth::login($user);

        return redirect()->route('dashboard');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => request('email'),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();
                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('success', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function logout(Request $request)
    {
        Audit::log('Logout', 'User', Auth::id(), 'User logged out');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function showProfile()
    {
        return view('auth.profile', ['user' => auth()->user()]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('success', 'Profile updated.');
    }
}
