@extends('layouts.guest')
@section('title', 'Forgot password')
@section('subtitle', 'We will email you a reset link')
@section('content')
<form method="POST" action="{{ route('password.email') }}">@csrf
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
    <button class="btn btn-primary px-4">Send reset link</button>
    <p class="mt-3 mb-0"><a href="{{ route('login') }}">Back to login</a></p>
</form>
@endsection
