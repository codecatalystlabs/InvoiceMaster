@extends('layouts.app')
@section('title', 'Clients')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search"><button class="btn btn-primary">Search</button></form>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addClient">Add client</button>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Company</th><th>Actions</th></tr></thead>
<tbody>
@forelse($clients as $c)
<tr>
    <td>{{ $c->name }}</td><td>{{ $c->email }}</td><td>{{ $c->phone }}</td><td>{{ $c->company }}</td>
    <td>
        <div class="row-actions">
            <a class="btn btn-sm btn-outline-secondary" href="{{ $c->portalUrl() }}" target="_blank" title="Client portal">Portal</a>
            <form method="POST" action="{{ route('clients.destroy', $c) }}" data-confirm="Delete client {{ $c->name }}? This cannot be undone.">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
@empty<tr><td colspan="5">No clients</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $clients->links() }}</div></div>
<div class="modal fade" id="addClient"><div class="modal-dialog"><form method="POST" action="{{ route('clients.store') }}" class="modal-content">@csrf
<div class="modal-header"><h5>New client</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input name="name" class="form-control mb-2" placeholder="Name" required>
    <input name="email" class="form-control mb-2" placeholder="Email">
    <input name="phone" class="form-control mb-2" placeholder="Phone">
    <input name="company" class="form-control mb-2" placeholder="Company">
    <textarea name="address" class="form-control" placeholder="Address" rows="2"></textarea>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@foreach($clients as $c)
<div class="modal fade" id="editClient{{ $c->id }}"><div class="modal-dialog"><form method="POST" action="{{ route('clients.update', $c) }}" class="modal-content">@csrf @method('PUT')
<div class="modal-header"><h5>Edit client</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input name="name" class="form-control mb-2" value="{{ $c->name }}" required>
    <input name="email" class="form-control mb-2" value="{{ $c->email }}" placeholder="Email">
    <input name="phone" class="form-control mb-2" value="{{ $c->phone }}" placeholder="Phone">
    <input name="company" class="form-control mb-2" value="{{ $c->company }}" placeholder="Company">
    <textarea name="address" class="form-control" rows="2" placeholder="Address">{{ $c->address }}</textarea>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endforeach
@endsection
