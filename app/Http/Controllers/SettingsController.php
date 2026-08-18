<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\User;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SettingsController extends Controller
{
    public function company()
    {
        $company = auth()->user()->company;
        $users = User::where('company_id', $company->id)->orderBy('name')->get();
        $invites = Invitation::whereNull('accepted_at')->latest()->get();

        return view('settings.company', compact('company', 'users', 'invites'));
    }

    public function updateCompany(Request $request)
    {
        $company = auth()->user()->company;
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'address' => 'nullable|string',
            'phone' => 'nullable|string',
            'email' => 'nullable|email',
            'currency' => 'required|string|max:10',
            'tagline' => 'nullable|string',
            'services_line' => 'nullable|string',
            'tax_rate' => 'nullable|numeric',
            'logo' => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        }
        unset($data['logo']);
        $company->update($data);

        return back()->with('success', 'Company profile updated.');
    }

    public function invite(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'role' => ['required', \Illuminate\Validation\Rule::in(role_options())],
        ]);
        $invite = Invitation::create([
            'email' => $data['email'],
            'role' => $data['role'],
            'token' => Str::random(40),
            'invited_by' => auth()->id(),
        ]);
        Audit::log('Invite', 'User', $invite->id, $invite->email);

        $url = route('invite.accept', $invite->token);
        try {
            Mail::to($invite->email)->send(new InvitationMail($invite, auth()->user()->company, $url));
        } catch (Throwable $e) {
            return back()->with('error', 'Invite created but email failed: '.$e->getMessage().' Share this link: '.$url);
        }

        return back()->with('success', 'Invite emailed to '.$invite->email);
    }
}
