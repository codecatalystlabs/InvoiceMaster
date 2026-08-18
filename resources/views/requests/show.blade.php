@extends('layouts.app')
@section('title', 'Change request')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('requests.index') }}" class="btn btn-secondary">Back</a>
</div>
<div class="row g-3">
    <div class="col-md-6">
        <div class="card"><div class="card-header">Current record</div><div class="card-body">
            @if($meal)
                <p>{{ $meal->user?->name }} · {{ $meal->meal_date?->format('d M Y') }} · {!! status_badge($meal->status) !!}</p>
                <p>Total {{ money($meal->total) }}</p>
                <ul class="mb-0">
                    @forelse($meal->lines as $line)
                        <li>{{ $line->item_name }} × {{ $line->qty }} · {{ money($line->line_total) }}</li>
                    @empty<li>Did not eat / no items</li>@endforelse
                </ul>
            @else
                <p>{{ $requestRow->entity_type }} #{{ $requestRow->entity_id }}</p>
            @endif
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header">Requested change</div><div class="card-body">
            <p>{{ $requestRow->requester?->name }} · {!! status_badge($requestRow->status) !!}</p>
            <p>{{ $requestRow->reason }}</p>
            @if(!empty($requestRow->payload['did_not_eat']))
                <p>Change to: did not eat</p>
            @else
                <ul>
                    @foreach($requestRow->payload['items'] ?? [] as $row)
                        <li>{{ $items[$row['item_id']]->name ?? ('Item '.$row['item_id']) }} × {{ $row['qty'] }}</li>
                    @endforeach
                </ul>
            @endif
            @if($requestRow->status === 'pending' && can_module('canteen.review'))
                <form method="POST" action="{{ route('requests.approve', $requestRow) }}" class="mb-2">@csrf
                    <textarea name="review_notes" class="form-control mb-2" placeholder="Optional note"></textarea>
                    <button class="btn btn-success w-100">Approve edit</button>
                </form>
                <form method="POST" action="{{ route('requests.refuse', $requestRow) }}" data-confirm="Refuse this edit request?">@csrf
                    <textarea name="review_notes" class="form-control mb-2" placeholder="Reason to refuse" required></textarea>
                    <button class="btn btn-outline-danger w-100">Refuse edit</button>
                </form>
            @elseif($requestRow->review_notes)
                <p class="mb-0 text-muted">{{ $requestRow->reviewer?->name }}: {{ $requestRow->review_notes }}</p>
            @endif
        </div></div>
    </div>
</div>
@endsection
