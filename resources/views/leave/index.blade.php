@extends('layouts.app')
@section('title', 'Leave')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4 mb-0">Leave review</h2>
    <a href="{{ route('leave.mine') }}" class="btn btn-outline-primary">My leave</a>
</div>
@if($pending->count())
<div class="card mb-3"><div class="card-header">Pending</div>
<div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Reason</th><th></th></tr></thead>
<tbody>
@foreach($pending as $req)
<tr>
    <td>{{ $req->employee?->name }}</td>
    <td>{{ $req->type?->name }}</td>
    <td>{{ $req->start_date->toDateString() }} – {{ $req->end_date->toDateString() }}</td>
    <td>{{ $req->days }}</td>
    <td>{{ $req->reason }}</td>
    <td class="d-flex gap-1">
        <form method="POST" action="{{ route('leave.approve', $req) }}" data-confirm="Approve this leave?">@csrf<button class="btn btn-sm btn-success">Approve</button></form>
        <form method="POST" action="{{ route('leave.reject', $req) }}" data-confirm="Reject this leave?">@csrf<button class="btn btn-sm btn-outline-danger">Reject</button></form>
    </td>
</tr>
@endforeach
</tbody></table></div></div>
@endif
<div class="card"><div class="card-header">Recent requests</div>
<div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Employee</th><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr></thead>
<tbody>
@forelse($recent as $req)
<tr>
    <td>{{ $req->employee?->name }}</td>
    <td>{{ $req->type?->name }}</td>
    <td>{{ $req->start_date->toDateString() }} – {{ $req->end_date->toDateString() }}</td>
    <td>{{ $req->days }}</td>
    <td>{!! status_badge($req->status) !!}</td>
</tr>
@empty<tr><td colspan="5" class="text-muted">No leave requests yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $recent->links() }}</div></div>
@endsection
