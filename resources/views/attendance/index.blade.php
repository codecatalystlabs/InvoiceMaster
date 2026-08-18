@extends('layouts.app')
@section('title', 'Attendance')
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <h2 class="h4 mb-0">Attendance</h2>
    <div class="d-flex gap-2">
        @if(!auth()->user()->seesOnlyOwnRecords())
            <a href="{{ route('attendance.punches') }}" class="btn btn-outline-secondary">Raw punches</a>
        @endif
    </div>
</div>
<form class="card card-body mb-3 row g-2" method="GET">
    <div class="col-md-3"><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
    <div class="col-md-3"><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
    @if($employees->count())
    <div class="col-md-3">
        <select name="employee_id" class="form-select">
            <option value="">All staff</option>
            @foreach($employees as $e)<option value="{{ $e->id }}" @selected((string)$employeeId===(string)$e->id)>{{ $e->name }}</option>@endforeach
        </select>
    </div>
    @endif
    <div class="col-md-2"><button class="btn btn-secondary w-100">Filter</button></div>
</form>
@if(can_module('hr') || can_module('leave.review'))
<form method="POST" action="{{ route('attendance.rebuild') }}" class="mb-3 d-flex gap-2" data-confirm="Rebuild the daily register from punches and leave for this date range?">
    @csrf
    <input type="hidden" name="from" value="{{ $from }}">
    <input type="hidden" name="to" value="{{ $to }}">
    <button class="btn btn-outline-primary">Rebuild register</button>
</form>
@endif
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Date</th><th>Employee</th><th>In</th><th>Out</th><th>Hours</th><th>Late</th><th>Status</th></tr></thead>
<tbody>
@forelse($days as $day)
<tr>
    <td>{{ $day->work_date->toDateString() }}</td>
    <td>{{ $day->employee?->name }}</td>
    <td>{{ $day->clock_in?->format('H:i') ?? '—' }}</td>
    <td>{{ $day->clock_out?->format('H:i') ?? '—' }}</td>
    <td>{{ minutes_label($day->worked_minutes) }}</td>
    <td>{{ minutes_label($day->late_minutes) }}</td>
    <td>{!! status_badge($day->status) !!}</td>
</tr>
@empty<tr><td colspan="7" class="text-muted">No attendance rows yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $days->links() }}</div></div>
@endsection
