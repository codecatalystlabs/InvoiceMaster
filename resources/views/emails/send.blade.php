@extends('layouts.app')
@section('title', $heading)
@section('content')
<div class="card" style="max-width:640px">
    <div class="card-body">
        <form method="POST" action="{{ $action }}">@csrf
            <div class="mb-3">
                <label class="form-label">To</label>
                <input type="email" name="to" class="form-control" value="{{ old('to', $to) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="6">{{ old('message', $defaultMessage) }}</textarea>
            </div>
            <p class="text-muted small">A PDF copy will be attached.</p>
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Send email</button>
                <a href="{{ $back }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
