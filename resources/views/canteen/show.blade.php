@extends('layouts.app')
@section('title', 'Meal '.$meal->meal_date?->format('d M Y'))
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('canteen.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        @if($meal->user_id === auth()->id() && $meal->isLocked() && $meal->status !== 'posted' && !$pendingRequest)
            <a href="{{ route('canteen.request', $meal) }}" class="btn btn-outline-primary">Request edit</a>
        @endif
        @if($meal->status === 'refused' && $meal->user_id === auth()->id())
            <a href="{{ route('canteen.today') }}" class="btn btn-primary">Declare again</a>
        @endif
        @if(can_module('canteen.review') && $meal->status === 'pending')
            <form method="POST" action="{{ route('canteen.approve', $meal) }}">@csrf<button class="btn btn-success">Approve</button></form>
        @endif
    </div>
</div>
<div class="row g-3">
    <div class="col-md-8">
        <div class="card"><div class="card-header">{{ $meal->user?->name }} · {{ $meal->meal_date?->format('l, d F Y') }}</div>
        <div class="card-body">
            <p>Status {!! status_badge($meal->status) !!} · Total <strong>{{ money($meal->total) }}</strong></p>
            @if($meal->did_not_eat)
                <p class="mb-0">Did not eat from the canteen.</p>
            @else
                <table class="table"><thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>
                @foreach($meal->lines as $line)
                    <tr><td>{{ $line->item_name }}</td><td>{{ $line->item_type }}</td><td>{{ $line->qty }}</td><td>{{ money($line->unit_price) }}</td><td>{{ money($line->line_total) }}</td></tr>
                @endforeach
                </tbody></table>
            @endif
            @if($meal->notes)<p class="mb-0"><span class="text-muted">Note:</span> {{ $meal->notes }}</p>@endif
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card"><div class="card-header">Review</div><div class="card-body">
            @if($meal->reviewed_at)
                <p>{{ $meal->reviewer?->name }} on {{ $meal->reviewed_at->format('d M Y H:i') }}</p>
                @if($meal->review_notes)<p>{{ $meal->review_notes }}</p>@endif
            @else
                <p class="text-muted">Waiting for a reviewer.</p>
            @endif
            @if($pendingRequest)
                <p class="mb-0">Edit request pending{{ can_module('requests') ? '.' : '.' }}
                    @if(can_module('requests'))
                        <a href="{{ route('requests.show', $pendingRequest) }}">Open request</a>
                    @endif
                </p>
            @endif
            @if(can_module('canteen.review') && $meal->status === 'pending')
                <form method="POST" action="{{ route('canteen.refuse', $meal) }}" class="mt-3" data-confirm="Refuse this meal declaration?">@csrf
                    <textarea name="review_notes" class="form-control mb-2" placeholder="Reason to refuse" required></textarea>
                    <button class="btn btn-outline-danger w-100">Refuse</button>
                </form>
            @endif
        </div></div>
    </div>
</div>
@endsection
