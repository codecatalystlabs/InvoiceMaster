@extends('layouts.app')
@section('title', 'Canteen month')
@section('content')
<form class="d-flex gap-2 mb-3">
    <select name="month" class="form-select" style="max-width:160px">
        @for($m=1;$m<=12;$m++)<option value="{{ $m }}" @selected($month===$m)>{{ now()->setMonth($m)->format('F') }}</option>@endfor
    </select>
    <input type="number" name="year" value="{{ $year }}" class="form-control" style="max-width:120px">
    <button class="btn btn-primary">View</button>
</form>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Approved (not posted)</h6><h3>{{ money($approvedTotal) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Already posted</h6><h3>{{ money($postedTotal) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Still pending review</h6><h3>{{ $pending }}</h3></div></div>
</div>
@if($close)
    <div class="alert alert-success">Closed on {{ $close->closed_at?->format('d M Y') }} by {{ $close->closer?->name }}. Expense
        @if($close->expense_id && can_module('expenses'))
            <a href="{{ route('expenses.show', $close->expense_id) }}">{{ $close->expense?->expense_number }}</a>
        @else
            recorded
        @endif
        · {{ money($close->total) }}
    </div>
@else
    <form method="POST" action="{{ route('canteen.month.close') }}" class="mb-3" data-confirm="Post approved meals for this month as one expense? Pending entries must be cleared first.">
        @csrf
        <input type="hidden" name="year" value="{{ $year }}">
        <input type="hidden" name="month" value="{{ $month }}">
        <button class="btn btn-primary" @disabled($pending>0 || $approvedTotal<=0)>Close month and post expense</button>
    </form>
@endif
<div class="row g-3">
    <div class="col-md-6">
        <div class="card"><div class="card-header">By person</div>
        <table class="table mb-0"><thead><tr><th>Name</th><th>Days</th><th>Total</th></tr></thead><tbody>
        @forelse($byPerson as $row)
            <tr><td>{{ $row['name'] }}</td><td>{{ $row['count'] }}</td><td>{{ money($row['total']) }}</td></tr>
        @empty<tr><td colspan="3" class="text-muted">None</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header">By food / sauce</div>
        <table class="table mb-0"><thead><tr><th>Item</th><th>Type</th><th>Qty</th><th>Total</th></tr></thead><tbody>
        @forelse($byItem as $row)
            <tr><td>{{ $row['name'] }}</td><td>{{ $row['type'] }}</td><td>{{ $row['qty'] }}</td><td>{{ money($row['total']) }}</td></tr>
        @empty<tr><td colspan="4" class="text-muted">None</td></tr>@endforelse
        </tbody></table></div>
    </div>
</div>
@endsection
