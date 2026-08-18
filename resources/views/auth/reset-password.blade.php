@extends('layouts.guest')
@section('title', 'Reset password')
@section('subtitle', 'Choose a new password')
@section('content')
<form method="POST" action="{{ route('password.update') }}">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email', $email) }}" required></div>
    <div class="mb-3"><label class="form-label">New password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <button class="btn btn-primary w-100">Reset password</button>
</form>
@endsection
