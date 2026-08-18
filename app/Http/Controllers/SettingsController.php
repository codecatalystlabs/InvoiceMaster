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
            'ura_tin' => 'nullable|string|max:40',
            'efris_device_no' => 'nullable|string|max:80',
            'whatsapp_token' => 'nullable|string',
            'whatsapp_phone_id' => 'nullable|string|max:40',
            'payment_provider' => 'nullable|string|max:40',
            'yo_username' => 'nullable|string|max:80',
            'yo_password' => 'nullable|string|max:120',
            'yo_mode' => 'nullable|in:sandbox,live',
        ]);

        $settings = $company->settings ?? [];
        foreach (['ura_tin', 'efris_device_no', 'whatsapp_token', 'whatsapp_phone_id', 'payment_provider', 'yo_username'] as $key) {
            if ($request->filled($key)) {
                $settings[$key] = $request->input($key);
            }
        }
        if ($request->filled('yo_mode')) {
            $settings['yo_mode'] = $request->input('yo_mode');
        }
        if ($request->filled('yo_password')) {
            $settings['yo_password'] = \App\Support\YoPayments::encryptSecret($request->input('yo_password'));
        }
        if (($settings['payment_provider'] ?? '') === '' && ! empty($settings['yo_username'])) {
            $settings['payment_provider'] = 'yo';
        }
        $data['settings'] = $settings;

        if ($request->hasFile('logo')) {
            $data['logo_path'] = $request->file('logo')->store('company', 'public');
        }
        unset(
            $data['logo'],
            $data['ura_tin'],
            $data['efris_device_no'],
            $data['whatsapp_token'],
            $data['whatsapp_phone_id'],
            $data['payment_provider'],
            $data['yo_username'],
            $data['yo_password'],
            $data['yo_mode']
        );
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
