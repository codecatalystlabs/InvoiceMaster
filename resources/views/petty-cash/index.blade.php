@extends('layouts.app')
@section('title', 'Petty cash')
@section('content')
<div class="d-flex justify-content-end mb-3"><a href="{{ route('petty-cash.create') }}" class="btn btn-primary">New fund</a></div>
<div class="row g-3">
@forelse($funds as $fund)
<div class="col-md-4">
    <div class="card h-100"><div class="card-body">
        <h5>{{ $fund->name }}</h5>
        <p class="text-muted">{{ $fund->department?->name ?? 'Company-wide' }} · Custodian {{ $fund->custodian?->name ?? '—' }}</p>
        <p class="mb-1">Balance <strong>{{ money($fund->balance) }}</strong></p>
        <p class="text-muted">Float limit {{ money($fund->float_limit) }}</p>
        <a href="{{ route('petty-cash.show', $fund) }}" class="btn btn-outline-primary">Open</a>
    </div></div>
</div>
@empty<p class="text-muted">No petty cash funds yet. Create one, then allocate budget into it.</p>@endforelse
</div>
@endsection
