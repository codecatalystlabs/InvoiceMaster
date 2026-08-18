@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="hero-card mb-3">
    <div>
        <h2>Hello, {{ auth()->user()->name }}</h2>
        <p>Log a meal only if you ate. You only see your own entries unless a reviewer asks you to correct one.</p>
    </div>
    <div class="signed-box">
        <img src="{{ auth()->user()->company?->logoUrl() ?? asset('images/logo.png') }}" alt="logo">
        <div>
            <div class="who">Signed in as</div>
            <div class="name">{{ auth()->user()->name }}</div>
            <div class="who">{{ auth()->user()->role }}</div>
        </div>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="info-tile"><div class="info-tile-head"><i class="bi bi-cup-hot"></i> This month</div><div class="info-tile-body"><strong>{{ money($stats['my_month']) }}</strong> on approved / posted meals</div></div></div>
    <div class="col-md-4"><div class="info-tile"><div class="info-tile-head"><i class="bi bi-hourglass-split"></i> Waiting review</div><div class="info-tile-body"><strong>{{ $stats['my_pending'] }}</strong> of your entries</div></div></div>
    <div class="col-md-4"><div class="info-tile"><div class="info-tile-head"><i class="bi bi-check2-circle"></i> Accepted days</div><div class="info-tile-body"><strong>{{ $stats['my_approved'] }}</strong> this month</div></div></div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4">
        <a class="action-tile featured" href="{{ route('canteen.today') }}">
            <div class="action-icon"><i class="bi bi-plus-lg"></i></div>
            <h6>{{ $stats['today'] ? 'Today’s meal' : 'Log a meal' }}</h6>
            <p>{{ $stats['today'] ? ucfirst($stats['today']->status).' · '.money($stats['today']->total) : 'If you ate, pick the source. Foods served with it are included.' }}</p>
        </a>
    </div>
    @if(can_module('canteen.review'))
    <div class="col-md-4">
        <a class="action-tile" href="{{ route('canteen.review') }}">
            <div class="action-icon"><i class="bi bi-check2-square"></i></div>
            <h6>Review queue</h6>
            <p>Accept or refuse today’s canteen entries.</p>
        </a>
    </div>
    @endif
    @if(can_module('requests'))
    <div class="col-md-4">
        <a class="action-tile" href="{{ route('requests.index') }}">
            <div class="action-icon"><i class="bi bi-pencil-square"></i></div>
            <h6>Change requests</h6>
            <p>{{ $pendingEdits }} waiting.</p>
        </a>
    </div>
    @endif
</div>
<div class="card"><div class="card-header">My recent meals</div>
<table class="table mb-0"><thead><tr><th>Date</th><th>Items</th><th>Total</th><th>Status</th></tr></thead><tbody>
@forelse($recentMeals as $meal)
<tr>
    <td><a href="{{ route('canteen.show', $meal) }}">{{ $meal->meal_date?->format('d M Y') }}</a></td>
    <td>{{ $meal->did_not_eat ? 'Did not eat' : $meal->lines->pluck('item_name')->join(', ') }}</td>
    <td>{{ money($meal->total) }}</td>
    <td>{!! status_badge($meal->status) !!}</td>
</tr>
@empty<tr><td colspan="4" class="text-muted">No meals yet.</td></tr>@endforelse
</tbody></table></div>
@endsection
