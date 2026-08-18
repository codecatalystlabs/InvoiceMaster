@extends('layouts.guest')
@section('title', 'Sign in')
@section('subtitle', 'Use your work email to open Invoice Master')
@section('content')
<form method="POST" action="{{ route('login') }}">@csrf
    <div class="mb-3">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus>
    </div>
    <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control" required>
    </div>
    <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="remember" id="remember">
        <label class="form-check-label" for="remember">Remember me</label>
    </div>
    <button class="btn btn-primary px-4">Sign in</button>
    <p class="mt-3 mb-0"><a href="{{ route('password.request') }}">Forgot password?</a></p>
    <p class="mt-2 mb-0"><a href="{{ route('register') }}">Create a company account</a></p>
</form>
@endsection
