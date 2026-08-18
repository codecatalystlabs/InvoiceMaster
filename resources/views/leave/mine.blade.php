@extends('layouts.app')
@section('title', 'My leave')
@section('content')
<h2 class="h4 mb-3">My leave</h2>
@if(!$employee)
<div class="alert alert-warning">Your login is not linked to an employee record. Ask HR to set <em>Linked user</em> on your employee card.</div>
@else
<div class="row g-3 mb-3">
@foreach($balances as $b)
<div class="col-md-3"><div class="stat-card"><h6>{{ $b->type?->name }}</h6><h3>{{ $b->remaining() }} <small class="fs-6 text-muted">/ {{ $b->entitled }}</small></h3></div></div>
@endforeach
</div>
<form method="POST" action="{{ route('leave.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-3">
        <label class="form-label">Type</label>
        <select name="leave_type_id" class="form-select" required>
            @foreach($types as $t)<option value="{{ $t->id }}">{{ $t->name }}{{ $t->paid ? '' : ' (unpaid)' }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-2"><label class="form-label">From</label><input type="date" name="start_date" class="form-control" required></div>
    <div class="col-md-2"><label class="form-label">To</label><input type="date" name="end_date" class="form-control" required></div>
    <div class="col-md-4"><label class="form-label">Reason</label><input name="reason" class="form-control"></div>
    <div class="col-md-1 d-flex align-items-end"><button class="btn btn-primary w-100">Ask</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th><th>Notes</th></tr></thead>
<tbody>
@forelse($requests as $req)
<tr>
    <td>{{ $req->type?->name }}</td>
    <td>{{ $req->start_date->toDateString() }} – {{ $req->end_date->toDateString() }}</td>
    <td>{{ $req->days }}</td>
    <td>{!! status_badge($req->status) !!}</td>
    <td>{{ $req->review_notes }}</td>
</tr>
@empty<tr><td colspan="5" class="text-muted">No requests yet.</td></tr>@endforelse
</tbody></table></div></div>
@endif
@endsection
