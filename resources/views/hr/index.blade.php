@extends('layouts.app')
@section('title', 'HR desk')
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h2 class="h4 mb-0">HR desk</h2>
    <form class="d-flex gap-2" method="GET">
        <input type="date" name="date" class="form-control" value="{{ $date }}">
        <button class="btn btn-secondary">Show</button>
    </form>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><h6>Active staff</h6><h3>{{ $headcount }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Present / late</h6><h3>{{ $days->whereIn('status', ['present','late','overtime'])->count() }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Absent</h6><h3>{{ $days->where('status', 'absent')->count() }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Pending leave</h6><h3>{{ $pendingLeave }}</h3></div></div>
</div>
<div class="d-flex flex-wrap gap-2 mb-3">
    @if(can_module('employees'))<a class="btn btn-outline-primary" href="{{ route('employees.index') }}">Employees</a>@endif
    @if(can_module('attendance'))<a class="btn btn-outline-primary" href="{{ route('attendance.index') }}">Attendance</a>@endif
    @if(can_module('leave'))<a class="btn btn-outline-primary" href="{{ route('leave.index') }}">Leave</a>@endif
    @if(can_module('hr.devices'))<a class="btn btn-outline-primary" href="{{ route('devices.index') }}">Machines</a>@endif
    <a class="btn btn-outline-secondary" href="{{ route('holidays.index') }}">Holidays</a>
</div>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="card"><div class="card-header">Attendance · {{ $date }}</div>
        <div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Employee</th><th>In</th><th>Out</th><th>Hours</th><th>Late</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($days as $day)
            <tr>
                <td>{{ $day->employee?->name }}</td>
                <td>{{ $day->clock_in?->format('H:i') ?? '—' }}</td>
                <td>{{ $day->clock_out?->format('H:i') ?? '—' }}</td>
                <td>{{ minutes_label($day->worked_minutes) }}</td>
                <td>{{ minutes_label($day->late_minutes) }}</td>
                <td>{!! status_badge($day->status) !!}</td>
            </tr>
            @empty<tr><td colspan="6" class="text-muted">No register for this date yet. Rebuild attendance or wait for machine punches.</td></tr>@endforelse
            </tbody>
        </table></div></div>
    </div>
    <div class="col-lg-4">
        <div class="card"><div class="card-header">On leave</div>
        <div class="list-group list-group-flush">
            @forelse($onLeave as $req)
            <div class="list-group-item">
                <strong>{{ $req->employee?->name }}</strong>
                <div class="small text-muted">{{ $req->type?->name }} · {{ $req->start_date->toDateString() }} – {{ $req->end_date->toDateString() }}</div>
            </div>
            @empty<div class="list-group-item text-muted">Nobody on leave today.</div>@endforelse
        </div></div>
    </div>
</div>
@endsection
