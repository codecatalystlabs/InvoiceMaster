@extends('layouts.app')
@section('title', 'Company settings')
@section('content')
<div class="row g-3">
<div class="col-md-7"><div class="card"><div class="card-header">Company profile</div><div class="card-body">
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data">@csrf @method('PUT')
    <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $company->name }}" required></div>
    <div class="mb-2"><label class="form-label">Address</label><textarea name="address" class="form-control">{{ $company->address }}</textarea></div>
    <div class="mb-2"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ $company->phone }}"></div>
    <div class="mb-2"><label class="form-label">Email</label><input name="email" class="form-control" value="{{ $company->email }}"></div>
    <div class="mb-2"><label class="form-label">Currency</label><input name="currency" class="form-control" value="{{ $company->currency }}"></div>
    <div class="mb-2"><label class="form-label">Tax rate %</label><input name="tax_rate" class="form-control" value="{{ $company->tax_rate }}"></div>
    <div class="mb-2"><label class="form-label">Tagline</label><input name="tagline" class="form-control" value="{{ $company->tagline }}"></div>
    <div class="mb-2"><label class="form-label">Services line</label><input name="services_line" class="form-control" value="{{ $company->services_line }}"></div>
    <div class="mb-2"><label class="form-label">Logo</label><input type="file" name="logo" class="form-control"></div>
    <hr>
    <h6>Payments, tax, WhatsApp</h6>
    <div class="mb-2"><label class="form-label">URA TIN</label><input name="ura_tin" class="form-control" value="{{ $company->setting('ura_tin') }}"></div>
    <div class="mb-2"><label class="form-label">EFRIS device number</label><input name="efris_device_no" class="form-control" value="{{ $company->setting('efris_device_no') }}"></div>
    <div class="mb-2">
        <label class="form-label">Payment aggregator</label>
        <select name="payment_provider" class="form-select">
            @php $provider = $company->setting('payment_provider', 'yo'); @endphp
            <option value="yo" @selected($provider === 'yo')>Yo Uganda (Mobile Money collections)</option>
            <option value="manual" @selected($provider === 'manual')>Manual confirm only</option>
            <option value="pesapal" @selected($provider === 'pesapal')>Pesapal (webhook stub)</option>
            <option value="flutterwave" @selected($provider === 'flutterwave')>Flutterwave (webhook stub)</option>
        </select>
    </div>
    <div class="border rounded p-3 mb-3">
        <h6 class="mb-2">Yo Uganda</h6>
        <p class="small text-muted">Business account API from <a href="https://paymentsweb.yo.co.ug/" target="_blank" rel="noopener">paymentsweb.yo.co.ug</a>. Used to receive MTN and Airtel payments on invoice pay links.</p>
        <div class="mb-2"><label class="form-label">API username</label><input name="yo_username" class="form-control" value="{{ $company->setting('yo_username') }}" autocomplete="off"></div>
        <div class="mb-2"><label class="form-label">API password</label><input type="password" name="yo_password" class="form-control" placeholder="{{ $company->setting('yo_password') ? 'Saved — leave blank to keep' : 'From your Yo business account' }}" autocomplete="new-password"></div>
        <div class="mb-2">
            <label class="form-label">Mode</label>
            <select name="yo_mode" class="form-select">
                @php $yoMode = $company->setting('yo_mode', 'sandbox'); @endphp
                <option value="sandbox" @selected($yoMode !== 'live')>Sandbox</option>
                <option value="live" @selected($yoMode === 'live')>Live</option>
            </select>
        </div>
        <p class="small text-muted mb-0">Give Yo this Instant Payment Notification URL (public HTTPS):<br><code>{{ url('/pay/webhook/yo') }}</code></p>
    </div>
    <div class="mb-2"><label class="form-label">WhatsApp token</label><input name="whatsapp_token" class="form-control" value="{{ $company->setting('whatsapp_token') }}"></div>
    <div class="mb-2"><label class="form-label">WhatsApp phone ID</label><input name="whatsapp_phone_id" class="form-control" value="{{ $company->setting('whatsapp_phone_id') }}"></div>
    <button class="btn btn-primary">Save</button>
</form>
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header">Invite teammate</div><div class="card-body">
<form method="POST" action="{{ route('settings.invite') }}">@csrf
    <input type="email" name="email" class="form-control mb-2" placeholder="Email" required>
    <select name="role" class="form-select mb-2">@foreach(role_options() as $r)<option>{{ $r }}</option>@endforeach</select>
    <button class="btn btn-primary">Send invite</button>
</form>
<ul class="mt-3">@foreach($invites as $i)<li>{{ $i->email }} ({{ $i->role }}) — {{ route('invite.accept',$i->token) }}</li>@endforeach</ul>
<h6 class="mt-3">Team</h6>
<ul>@foreach($users as $u)<li>{{ $u->name }} — {{ $u->role }}</li>@endforeach</ul>
</div></div></div>
</div>
@endsection
