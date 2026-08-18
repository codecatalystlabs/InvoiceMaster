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
