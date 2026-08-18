@extends('layouts.app')
@section('title', 'Departments')
@section('content')
<form method="POST" action="{{ route('departments.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-4"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-2"><input name="code" class="form-control" placeholder="Code"></div>
    <div class="col-md-4"><select name="head_user_id" class="form-select"><option value="">Head (optional)</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
</form>
<div class="card"><table class="table mb-0"><thead><tr><th>Name</th><th>Code</th><th>Head</th><th>People</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($departments as $d)
<tr>
    <td>{{ $d->name }}</td><td>{{ $d->code }}</td><td>{{ $d->head?->name ?? '—' }}</td><td>{{ $d->users_count }}</td><td>{{ $d->is_active ? 'Active' : 'Inactive' }}</td>
    <td><button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#ed{{ $d->id }}">Edit</button></td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No departments yet.</td></tr>@endforelse
</tbody></table></div>
@foreach($departments as $d)
<div class="modal fade" id="ed{{ $d->id }}"><div class="modal-dialog"><form method="POST" action="{{ route('departments.update', $d) }}" class="modal-content">@csrf @method('PUT')
<div class="modal-header"><h5>Edit {{ $d->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input name="name" class="form-control mb-2" value="{{ $d->name }}" required>
    <input name="code" class="form-control mb-2" value="{{ $d->code }}">
    <select name="head_user_id" class="form-select mb-2"><option value="">Head</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($d->head_user_id==$u->id)>{{ $u->name }}</option>@endforeach</select>
    <select name="is_active" class="form-select"><option value="1" @selected($d->is_active)>Active</option><option value="0" @selected(!$d->is_active)>Inactive</option></select>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endforeach
@endsection
