@extends('layouts.app')
@section('title', 'Attendance machines')
@section('content')
<h2 class="h4 mb-2">Attendance machines</h2>
<p class="text-muted mb-3">ZKTeco and similar ADMS clocks push punches here. Set the machine server URL to this site, then save the device serial number below. Employee <strong>machine PIN</strong> on each staff record must match the PIN on the clock.</p>
<div class="alert alert-secondary">
    <div><strong>ADMS / iclock URL</strong> (paste into the machine as Cloud / ADMS server)</div>
    <code>{{ url('/') }}</code>
    <div class="small mt-2">The clock will call <code>{{ url('/iclock/cdata') }}</code> and <code>{{ url('/iclock/getrequest') }}</code>.</div>
    <div class="small">JSON push: <code>POST {{ url('/api/v1/attendance/punches') }}</code> with header <code>X-Device-Key</code> and body <code>{"punches":[{"pin":"1","punched_at":"2026-06-30 08:01:00","status":0}]}</code>. Status 0 = in, 1 = out, 4 = OT in, 5 = OT out.</div>
</div>
<form method="POST" action="{{ route('devices.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Device name" required></div>
    <div class="col-md-2"><input name="serial_number" class="form-control" placeholder="Serial (SN)"></div>
    <div class="col-md-2"><input name="location" class="form-control" placeholder="Location"></div>
    <div class="col-md-2"><input type="time" name="work_start" class="form-control" value="08:00"></div>
    <div class="col-md-2"><input type="time" name="work_end" class="form-control" value="17:00"></div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
</form>
@foreach($devices as $d)
<div class="card mb-3">
    <div class="card-body">
        <form method="POST" action="{{ route('devices.update', $d) }}" class="row g-2 align-items-end">@csrf @method('PUT')
            <div class="col-md-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $d->name }}" required></div>
            <div class="col-md-2"><label class="form-label">Serial</label><input name="serial_number" class="form-control" value="{{ $d->serial_number }}"></div>
            <div class="col-md-2"><label class="form-label">Location</label><input name="location" class="form-control" value="{{ $d->location }}"></div>
            <div class="col-md-1"><label class="form-label">Start</label><input type="time" name="work_start" class="form-control" value="{{ substr((string) $d->work_start, 0, 5) }}"></div>
            <div class="col-md-1"><label class="form-label">End</label><input type="time" name="work_end" class="form-control" value="{{ substr((string) $d->work_end, 0, 5) }}"></div>
            <div class="col-md-1"><label class="form-label">Grace</label><input type="number" name="late_grace_minutes" class="form-control" value="{{ $d->late_grace_minutes }}"></div>
            <div class="col-md-1"><label class="form-label">On</label>
                <select name="is_active" class="form-select"><option value="1" @selected($d->is_active)>Yes</option><option value="0" @selected(!$d->is_active)>No</option></select>
            </div>
            <div class="col-md-1"><button class="btn btn-primary w-100">Save</button></div>
        </form>
        <div class="small text-muted mt-2">
            Device key: <code>{{ $d->device_key }}</code>
            · Last seen: {{ $d->last_seen_at?->format('Y-m-d H:i') ?? 'never' }}
            · {{ $d->vendor }}
        </div>
        <form method="POST" action="{{ route('devices.destroy', $d) }}" class="mt-2" data-confirm="Remove device {{ $d->name }}? Punches already saved stay.">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Remove</button>
        </form>
    </div>
</div>
@endforeach
@if($devices->isEmpty())
<div class="text-muted">No machines registered yet.</div>
@endif
@endsection
