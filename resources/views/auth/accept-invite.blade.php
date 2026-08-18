@extends('layouts.guest')
@section('title', 'Accept invite')
@section('subtitle', 'Join '.$invite->email)
@section('content')
<form method="POST">@csrf
    <div class="mb-3"><label class="form-label">Your name</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Confirm password</label><input type="password" name="password_confirmation" class="form-control" required></div>
    <button class="btn btn-primary w-100">Join workspace</button>
</form>
@endsection
