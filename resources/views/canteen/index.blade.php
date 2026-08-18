@extends('layouts.app')
@section('title', 'Canteen meals')
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2 flex-wrap">
        <input type="date" name="from" value="{{ request('from') }}" class="form-control">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(['pending','approved','refused','posted'] as $s)
                <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <span class="btn btn-outline-secondary disabled">This month {{ money($monthTotal) }}</span>
        <a href="{{ route('canteen.today') }}" class="btn btn-primary">Log a meal</a>
    </div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Date</th>@if(!auth()->user()->seesOnlyOwnRecords())<th>Person</th>@endif<th>Items</th><th>Total</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($meals as $meal)
<tr>
    <td>{{ $meal->meal_date?->format('d M Y') }}</td>
    @if(!auth()->user()->seesOnlyOwnRecords())<td>{{ $meal->user?->name }}</td>@endif
    <td>
        @if($meal->did_not_eat) Did not eat
        @else {{ $meal->lines->map(fn($l) => $l->item_name.($l->qty>1?' ×'.$l->qty:''))->join(', ') }}
        @endif
    </td>
    <td>{{ money($meal->total) }}</td>
    <td>{!! status_badge($meal->status) !!}</td>
    <td><a href="{{ route('canteen.show', $meal) }}" class="btn btn-sm btn-outline-secondary">View</a></td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No meals recorded.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $meals->links() }}</div></div>
@endsection
