@extends('layouts.app')
@section('title', 'Chart of Accounts')
@section('content')
<div class="card mb-3"><div class="card-body">
<form method="POST" action="{{ route('accounts.store') }}" class="row g-2">@csrf
    <div class="col-md-2"><input name="account_code" class="form-control" placeholder="Code" required></div>
    <div class="col-md-3"><input name="account_name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-3"><select name="account_type" class="form-select">@foreach(['Asset','Liability','Equity','Revenue','Expense'] as $t)<option>{{ $t }}</option>@endforeach</select></div>
    <div class="col-md-3"><input name="description" class="form-control" placeholder="Description"></div>
    <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
</form>
</div></div>
@foreach($accounts as $type => $rows)
<h5 class="mt-3">{{ $type }}</h5>
<div class="card mb-3"><table class="table mb-0"><thead><tr><th>Code</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead><tbody>
@foreach($rows as $a)
<tr>
    <td>{{ $a->account_code }}</td>
    <td>{{ $a->account_name }}</td>
    <td>{{ $a->description }}</td>
    <td>
        <div class="row-actions">
            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAccount{{ $a->id }}" title="Edit"><i class="bi bi-pencil"></i></button>
            <form method="POST" action="{{ route('accounts.destroy', $a) }}" data-confirm="Delete account {{ $a->account_code }}? This cannot be undone.">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
            </form>
        </div>
    </td>
</tr>
@endforeach
</tbody></table></div>
@endforeach
@foreach($accounts as $rows)
    @foreach($rows as $a)
    <div class="modal fade" id="editAccount{{ $a->id }}"><div class="modal-dialog"><form method="POST" action="{{ route('accounts.update', $a) }}" class="modal-content">@csrf @method('PUT')
    <div class="modal-header"><h5>Edit account</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
        <input name="account_code" class="form-control mb-2" value="{{ $a->account_code }}" required>
        <input name="account_name" class="form-control mb-2" value="{{ $a->account_name }}" required>
        <select name="account_type" class="form-select mb-2">@foreach(['Asset','Liability','Equity','Revenue','Expense'] as $t)<option value="{{ $t }}" @selected($a->account_type===$t)>{{ $t }}</option>@endforeach</select>
        <input name="description" class="form-control mb-2" value="{{ $a->description }}">
        <div class="form-check"><input class="form-check-input" type="checkbox" name="is_active" value="1" id="active{{ $a->id }}" @checked($a->is_active)><label class="form-check-label" for="active{{ $a->id }}">Active</label></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary">Save</button></div>
    </form></div></div>
    @endforeach
@endforeach
@endsection
