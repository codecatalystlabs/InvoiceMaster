@extends('layouts.guest')
@section('title', 'Register')
@section('subtitle', 'Create your company workspace')
@section('content')
<form method="POST" action="{{ route('register') }}">@csrf
    <div class="mb-3"><label class="form-label">Company name</label><input name="company_name" class="form-control" value="{{ old('company_name') }}" required></div>
    <div class="mb-3"><label class="form-label">Your name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <button class="btn btn-primary px-4">Create account</button>
    <p class="mt-3 mb-0"><a href="{{ route('login') }}">Already have an account?</a></p>
</form>
@endsection
