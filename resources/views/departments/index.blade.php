@extends('layouts.app')
@section('title', 'Organisation')
@section('content')
<form method="POST" action="{{ route('departments.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-4"><input name="name" class="form-control" placeholder="Department name" required></div>
    <div class="col-md-2"><input name="code" class="form-control" placeholder="Code"></div>
    <div class="col-md-4"><select name="head_user_id" class="form-select"><option value="">Head (optional)</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead>
<tr>
    <th>Code</th>
    <th>Department</th>
    <th>Head</th>
    <th>Divisions</th>
    <th>Positions</th>
    <th>Staff</th>
    <th>Status</th>
    <th></th>
</tr>
</thead>
<tbody>
@forelse($departments as $d)
<tr>
    <td>{{ $d->code ?: '—' }}</td>
    <td><a href="{{ route('departments.show', $d) }}">{{ $d->name }}</a></td>
    <td>{{ $d->head?->name ?? '—' }}</td>
    <td>{{ $d->divisions_count }}</td>
    <td>{{ $d->positions_count }}</td>
    <td>{{ $d->employees_count }}</td>
    <td>{!! status_badge($d->is_active ? 'Active' : 'Inactive') !!}</td>
    <td>
        <div class="row-actions">
            <a class="btn btn-sm btn-outline-secondary" href="{{ route('departments.show', $d) }}" title="Open"><i class="bi bi-eye"></i></a>
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ed{{ $d->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
        </div>
    </td>
</tr>
@empty
<tr><td colspan="8" class="text-muted">No departments yet.</td></tr>
@endforelse
</tbody>
</table></div></div>
@foreach($departments as $d)
<div class="modal fade" id="ed{{ $d->id }}"><div class="modal-dialog"><form method="POST" action="{{ route('departments.update', $d) }}" class="modal-content">@csrf @method('PUT')
<div class="modal-header"><h5>Edit {{ $d->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Name</label>
    <input name="name" class="form-control mb-2" value="{{ $d->name }}" required>
    <label class="form-label">Code</label>
    <input name="code" class="form-control mb-2" value="{{ $d->code }}">
    <label class="form-label">Head</label>
    <select name="head_user_id" class="form-select mb-2"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($d->head_user_id==$u->id)>{{ $u->name }}</option>@endforeach</select>
    <label class="form-label">Status</label>
    <select name="is_active" class="form-select"><option value="1" @selected($d->is_active)>Active</option><option value="0" @selected(!$d->is_active)>Inactive</option></select>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endforeach
@endsection
