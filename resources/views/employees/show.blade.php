@extends('layouts.app')
@section('title', $employee->name)
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h4 mb-0">{{ $employee->name }}</h2>
        <div class="text-muted">{{ $employee->number }} · {{ $employee->position?->name ?? $employee->job_title ?: 'Staff' }} · PIN {{ $employee->pin() }}</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('employees.edit', $employee) }}" class="btn btn-primary">Edit</a>
        <a href="{{ route('employees.index') }}" class="btn btn-secondary">Back</a>
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Department</h6><h3 class="h5">{{ $employee->department?->name ?? '—' }}{{ $employee->division ? ' · '.$employee->division->name : '' }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Supervisor</h6><h3 class="h5">{{ $employee->supervisor?->name ?? '—' }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Status</h6><h3 class="h5">{!! status_badge($employee->status) !!} {{ $employee->employment_type }}</h3></div></div>
</div>
<div class="card mb-3"><div class="card-body row g-2 small">
    <div class="col-md-4">Login user: {{ $employee->user?->name ?? '—' }}</div>
    <div class="col-md-4">Phone: {{ $employee->phone ?: '—' }}</div>
    <div class="col-md-4">Email: {{ $employee->email ?: '—' }}</div>
    <div class="col-md-4">TIN: {{ $employee->tin ?: '—' }}</div>
    <div class="col-md-4">NSSF: {{ $employee->nssf_number ?: '—' }}</div>
    <div class="col-md-4">National ID: {{ $employee->national_id ?: '—' }}</div>
    <div class="col-md-4">Next of kin: {{ $employee->next_of_kin ?: '—' }} {{ $employee->next_of_kin_phone }}</div>
    <div class="col-md-4">Pay: {{ $employee->pay_method }} {{ $employee->bank_name }} {{ $employee->pay_account }}</div>
    <div class="col-md-4">Basic {{ money($employee->basic_salary) }} · Allowances {{ money($employee->allowances) }}</div>
</div></div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card mb-3"><div class="card-header">Leave balances {{ now()->year }}</div>
        <table class="table mb-0"><thead><tr><th>Type</th><th>Entitled</th><th>Taken</th><th>Left</th></tr></thead><tbody>
        @forelse($balances as $b)
        <tr><td>{{ $b->type?->name }}</td><td>{{ $b->entitled }}</td><td>{{ $b->taken }}</td><td>{{ $b->remaining() }}</td></tr>
        @empty<tr><td colspan="4" class="text-muted">No balances yet.</td></tr>@endforelse
        </tbody></table></div>
        <div class="card"><div class="card-header">Recent leave</div>
        <table class="table mb-0"><thead><tr><th>Type</th><th>Dates</th><th></th></tr></thead><tbody>
        @forelse($requests as $req)
        <tr><td>{{ $req->type?->name }}</td><td>{{ $req->start_date->toDateString() }} – {{ $req->end_date->toDateString() }}</td><td>{!! status_badge($req->status) !!}</td></tr>
        @empty<tr><td colspan="3" class="text-muted">None.</td></tr>@endforelse
        </tbody></table></div>
    </div>
    <div class="col-lg-7">
        <div class="card"><div class="card-header">This month</div>
        <div class="table-responsive"><table class="table mb-0">
            <thead><tr><th>Date</th><th>In</th><th>Out</th><th>Hours</th><th></th></tr></thead>
            <tbody>
            @forelse($days as $day)
            <tr>
                <td>{{ $day->work_date->toDateString() }}</td>
                <td>{{ $day->clock_in?->format('H:i') ?? '—' }}</td>
                <td>{{ $day->clock_out?->format('H:i') ?? '—' }}</td>
                <td>{{ minutes_label($day->worked_minutes) }}</td>
                <td>{!! status_badge($day->status) !!}</td>
            </tr>
            @empty<tr><td colspan="5" class="text-muted">No attendance this month.</td></tr>@endforelse
            </tbody>
        </table></div></div>
    </div>
</div>
@endsection
