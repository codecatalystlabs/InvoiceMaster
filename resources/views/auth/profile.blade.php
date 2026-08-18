@extends('layouts.app')
@section('title', 'Profile')
@section('content')
<div class="card"><div class="card-body">
<form method="POST" action="{{ route('profile') }}">@csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $user->name }}" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
    <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control"></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control"></div>
    <button class="btn btn-primary">Save</button>
</form>
</div></div>
@endsection
