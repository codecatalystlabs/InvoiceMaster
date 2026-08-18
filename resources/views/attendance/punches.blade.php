@extends('layouts.app')
@section('title', 'Machine punches')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4 mb-0">Machine punches</h2>
    <a href="{{ route('attendance.index') }}" class="btn btn-secondary">Register</a>
</div>
<form class="card card-body mb-3 row g-2" method="GET">
    <div class="col-md-4"><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
    <div class="col-md-4"><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
    <div class="col-md-2"><button class="btn btn-secondary w-100">Filter</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>When</th><th>PIN</th><th>Employee</th><th>In/Out</th><th>Device</th><th>Source</th></tr></thead>
<tbody>
@forelse($punches as $p)
<tr>
    <td>{{ $p->punched_at?->format('Y-m-d H:i:s') }}</td>
    <td>{{ $p->machine_pin }}</td>
    <td>{{ $p->employee?->name ?? 'Unmatched' }}</td>
    <td>{{ in_array((int)$p->status, [1,5], true) ? 'Out' : 'In' }} ({{ $p->status }})</td>
    <td>{{ $p->device?->name ?? '—' }}</td>
    <td>{{ $p->source }}</td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No punches in this range.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $punches->links() }}</div></div>
@endsection
