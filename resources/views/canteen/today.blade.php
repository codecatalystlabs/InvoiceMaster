@extends('layouts.app')
@section('title', 'Log a meal')
@section('content')
<div class="hero-card mb-3">
    <div>
        <h2>Log a canteen meal</h2>
        <p>{{ now()->format('l, d F Y') }}. Optional — only if you ate. Pay for the source (chicken, beans, and so on). The foods served with it are included in that amount.</p>
    </div>
</div>
@if($meal && $meal->status === 'refused')
    <div class="alert alert-danger">Your last declaration was refused{{ $meal->review_notes ? ': '.$meal->review_notes : '.' }} Submit a new one if you still ate.</div>
@endif
<form method="POST" action="{{ route('canteen.store') }}" class="card card-body">
    @csrf
    @include('canteen.partials.picker', ['items' => $items])
    <div class="mt-3">
        <label class="form-label">Note (optional)</label>
        <input name="notes" class="form-control" value="{{ old('notes', $meal->notes ?? '') }}" placeholder="Guest plate, extra portion…">
    </div>
    <button class="btn btn-primary mt-3">Send for review</button>
</form>
@endsection
