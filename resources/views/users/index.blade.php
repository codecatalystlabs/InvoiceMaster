@extends('layouts.app')
@section('title', 'Users')
@section('content')
<form method="POST" action="{{ route('users.store') }}" class="card card-body mb-3">@csrf
    <div class="row g-2">
        <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
        <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
        <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
        <div class="col-md-3"><select name="role" class="form-select">@foreach($roles as $r)<option>{{ $r }}</option>@endforeach</select></div>
        <div class="col-md-3"><select name="department_id" class="form-select"><option value="">Department</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Add user</button></div>
    </div>
    <details class="mt-2"><summary class="small text-muted">Override modules (optional)</summary>
        <div class="row mt-2">
            @foreach($catalog as $key => $label)
                <div class="col-md-3 form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="{{ $key }}" id="n{{ $key }}"><label class="form-check-label" for="n{{ $key }}">{{ $label }}</label></div>
            @endforeach
        </div>
    </details>
</form>
<div class="card"><table class="table mb-0"><thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@foreach($users as $u)
<tr>
    <td>{{ $u->name }}</td>
    <td>{{ $u->email }}</td>
    <td>{{ $u->role }}</td>
    <td>{{ $u->department?->name ?? '—' }}</td>
    <td>{{ $u->status }}</td>
    <td>
        <div class="row-actions">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editUser{{ $u->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
            @if($u->id !== auth()->id())
            <form method="POST" action="{{ route('users.destroy', $u) }}" data-confirm="Delete user {{ $u->name }}? This cannot be undone.">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
            @endif
        </div>
    </td>
</tr>
@endforeach
</tbody></table></div>
<div class="card-body">{{ $users->links() }}</div>
@foreach($users as $u)
<div class="modal fade" id="editUser{{ $u->id }}"><div class="modal-dialog modal-lg"><form method="POST" action="{{ route('users.update', $u) }}" class="modal-content">@csrf @method('PUT')
<div class="modal-header"><h5>Edit user</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input name="name" class="form-control mb-2" value="{{ $u->name }}" required>
    <input type="email" name="email" class="form-control mb-2" value="{{ $u->email }}" required>
    <select name="role" class="form-select mb-2">@foreach($roles as $r)<option value="{{ $r }}" @selected($u->role===$r)>{{ $r }}</option>@endforeach</select>
    <select name="department_id" class="form-select mb-2"><option value="">Department</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected($u->department_id==$d->id)>{{ $d->name }}</option>@endforeach</select>
    <select name="status" class="form-select mb-2">@foreach(['Active','Inactive'] as $s)<option value="{{ $s }}" @selected($u->status===$s)>{{ $s }}</option>@endforeach</select>
    <input type="password" name="password" class="form-control mb-2" placeholder="New password (leave blank to keep)">
    <p class="small text-muted mb-1">Leave modules unchecked to use the role defaults.</p>
    <div class="row">
        @foreach($catalog as $key => $label)
            <div class="col-md-4 form-check"><input class="form-check-input" type="checkbox" name="modules[]" value="{{ $key }}" id="u{{ $u->id }}{{ $key }}" @checked(in_array($key, $u->modules ?? [], true))><label class="form-check-label" for="u{{ $u->id }}{{ $key }}">{{ $label }}</label></div>
        @endforeach
    </div>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endforeach
@endsection
