@extends('layouts.app')
@section('title', 'Assets')
@section('content')
<div class="d-flex justify-content-between mb-3"><h5>Value {{ money($total) }}</h5><div class="d-flex gap-2"><a href="{{ route('exports.download','assets') }}" class="btn btn-outline-success">CSV</a><a href="{{ route('assets.create') }}" class="btn btn-primary">Add asset</a></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>#</th><th>Name</th><th>Category</th><th>Purchase</th><th>Value</th><th>Actions</th></tr></thead><tbody>
@forelse($assets as $a)
<tr>
    <td><a href="{{ route('assets.show',$a) }}">{{ $a->asset_number }}</a></td>
    <td>{{ $a->asset_name }}</td>
    <td>{{ $a->category }}</td>
    <td>{{ money($a->purchase_price) }}</td>
    <td>{{ money($a->current_value) }}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('assets.show', $a),
            'edit' => route('assets.edit', $a),
            'delete' => route('assets.destroy', $a),
            'confirm' => 'Delete asset '.$a->asset_number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="6">None</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $assets->links() }}</div></div>
@endsection
