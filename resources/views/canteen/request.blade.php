@extends('layouts.app')
@section('title', 'Request meal edit')
@section('content')
<p class="text-muted">The current entry stays as-is until a reviewer accepts this change.</p>
<form method="POST" action="{{ route('canteen.request.store', $meal) }}" class="card card-body">
    @csrf
    <div class="mb-3">
        <label class="form-label">Why does this need changing?</label>
        <textarea name="reason" class="form-control" required>{{ old('reason') }}</textarea>
    </div>
    @include('canteen.partials.picker', ['items' => $items, 'selected' => $selected])
    <div class="mt-3">
        <label class="form-label">Note</label>
        <input name="notes" class="form-control" value="{{ old('notes', $meal->notes) }}">
    </div>
    <button class="btn btn-primary mt-3">Send edit request</button>
</form>
@endsection
