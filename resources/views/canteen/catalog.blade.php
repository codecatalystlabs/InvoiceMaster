@extends('layouts.app')
@section('title', 'Sources and food')
@section('content')
<p class="text-muted">Set prices on the source (chicken, beans, beef). Foods served with it are included and should not be priced.</p>
<form method="POST" action="{{ route('canteen.items.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-2"><select name="type" class="form-select">@foreach($types as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-2"><input name="unit" class="form-control" value="serving" required></div>
    <div class="col-md-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price" value="0" required></div>
    <div class="col-md-1"><input type="number" name="sort_order" class="form-control" placeholder="#"></div>
    <div class="col-md-2 form-check mt-2"><input type="hidden" name="is_priced" value="0"><input class="form-check-input" type="checkbox" name="is_priced" value="1" id="np" checked><label class="form-check-label" for="np">Priced</label></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Add item</button></div>
</form>
@foreach($types as $type => $label)
    @if(($items[$type] ?? collect())->isEmpty()) @continue @endif
    <h6>{{ $label }}</h6>
    <div class="card mb-3"><table class="table mb-0"><thead><tr><th>Name</th><th>Unit</th><th>Price</th><th>Priced</th><th>Active</th><th></th></tr></thead><tbody>
    @foreach($items[$type] as $item)
    <tr>
        <td>{{ $item->name }}</td>
        <td>{{ $item->unit }}</td>
        <td>{{ $item->is_priced ? money($item->price) : 'Included' }}</td>
        <td>{{ $item->is_priced ? 'Yes' : 'No' }}</td>
        <td>{{ $item->is_active ? 'Yes' : 'No' }}</td>
        <td>
            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editItem{{ $item->id }}">Edit</button>
            <form method="POST" action="{{ route('canteen.items.destroy', $item) }}" class="d-inline" onsubmit="return confirm('Remove {{ $item->name }}?')">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
        </td>
    </tr>
    @endforeach
    </tbody></table></div>
@endforeach
@foreach($items->flatten() as $item)
<div class="modal fade" id="editItem{{ $item->id }}"><div class="modal-dialog"><form method="POST" action="{{ route('canteen.items.update', $item) }}" class="modal-content">@csrf @method('PUT')
<div class="modal-header"><h5>Edit {{ $item->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <input name="name" class="form-control mb-2" value="{{ $item->name }}" required>
    <select name="type" class="form-select mb-2">@foreach($types as $k=>$v)<option value="{{ $k }}" @selected($item->type===$k)>{{ $v }}</option>@endforeach</select>
    <input name="unit" class="form-control mb-2" value="{{ $item->unit }}">
    <input type="number" step="0.01" name="price" class="form-control mb-2" value="{{ $item->price }}">
    <input type="number" name="sort_order" class="form-control mb-2" value="{{ $item->sort_order }}">
    <div class="form-check mb-2"><input type="hidden" name="is_priced" value="0"><input class="form-check-input" type="checkbox" name="is_priced" value="1" id="p{{ $item->id }}" @checked($item->is_priced)><label class="form-check-label" for="p{{ $item->id }}">This item is priced</label></div>
    <select name="is_active" class="form-select"><option value="1" @selected($item->is_active)>Active</option><option value="0" @selected(!$item->is_active)>Inactive</option></select>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endforeach
@endsection
