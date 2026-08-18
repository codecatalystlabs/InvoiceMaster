@extends('layouts.app')
@section('title', 'Canteen review')
@section('content')
<form class="d-flex gap-2 mb-3">
    <input type="date" name="date" value="{{ $date }}" class="form-control" style="max-width:220px">
    <button class="btn btn-primary">Load day</button>
</form>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h5 class="mb-0">Pending · {{ $pending->count() }}</h5>
    @if($pending->count())
    <form method="POST" action="{{ route('canteen.bulk') }}" data-confirm="Approve every pending entry for this day?">
        @csrf<input type="hidden" name="date" value="{{ $date }}">
        <button class="btn btn-success">Approve all for this day</button>
    </form>
    @endif
</div>
@forelse($pending as $meal)
<div class="card mb-2"><div class="card-body d-flex justify-content-between flex-wrap gap-2">
    <div>
        <strong>{{ $meal->user?->name }}</strong>
        <div class="text-muted small">{{ $meal->did_not_eat ? 'Did not eat' : $meal->lines->map(fn($l) => $l->item_name.($l->qty>1?' ×'.$l->qty:''))->join(', ') }}</div>
        @if($meal->notes)<div class="small">{{ $meal->notes }}</div>@endif
    </div>
    <div class="text-end">
        <div class="fw-bold">{{ money($meal->total) }}</div>
        <div class="d-flex gap-2 mt-2">
            <a href="{{ route('canteen.show', $meal) }}" class="btn btn-sm btn-outline-secondary">View</a>
            <form method="POST" action="{{ route('canteen.approve', $meal) }}">@csrf<button class="btn btn-sm btn-success">Accept</button></form>
            <form method="POST" action="{{ route('canteen.refuse', $meal) }}" data-confirm="Refuse this meal declaration?">@csrf
                <input type="hidden" name="review_notes" value="Please declare again with the correct items.">
                <button class="btn btn-sm btn-outline-danger">Refuse</button>
            </form>
        </div>
    </div>
</div></div>
@empty<p class="text-muted">No pending meals for this day.</p>@endforelse

@if($editRequests->count())
<h5 class="mt-4">Pending edit requests</h5>
@foreach($editRequests as $row)
<div class="card mb-2"><div class="card-body d-flex justify-content-between">
    <div><strong>{{ $row->requester?->name }}</strong> · {{ $row->entity_type }} #{{ $row->entity_id }}<div class="text-muted small">{{ $row->reason }}</div></div>
    <a href="{{ route('requests.show', $row) }}" class="btn btn-sm btn-outline-primary">Review</a>
</div></div>
@endforeach
@endif

<h5 class="mt-4">Already reviewed</h5>
<div class="card"><table class="table mb-0"><thead><tr><th>Person</th><th>Status</th><th>Total</th><th>Reviewer</th></tr></thead><tbody>
@forelse($done as $meal)
<tr><td><a href="{{ route('canteen.show', $meal) }}">{{ $meal->user?->name }}</a></td><td>{!! status_badge($meal->status) !!}</td><td>{{ money($meal->total) }}</td><td>{{ $meal->reviewer?->name }}</td></tr>
@empty<tr><td colspan="4" class="text-muted">None</td></tr>@endforelse
</tbody></table></div>
@endsection
