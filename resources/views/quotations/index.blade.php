@extends('layouts.app')
@section('title', 'Quotations')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search"><select name="status" class="form-select"><option value="">All</option>@foreach(['Draft','Sent','Accepted','Rejected','Converted'] as $s)<option value="{{ $s }}" @selected($status===$s)>{{ $s }}</option>@endforeach</select><button class="btn btn-primary">Filter</button></form>
    <div class="d-flex gap-2"><a href="{{ route('exports.download','quotations') }}" class="btn btn-outline-success">CSV</a><a href="{{ route('quotations.create') }}" class="btn btn-primary">New quotation</a></div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>#</th><th>Date</th><th>Client</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($quotations as $row)
<tr>
    <td><a href="{{ route('quotations.show',$row) }}">{{ $row->quotation_number }}</a></td>
    <td>{{ $row->date?->format('d M Y') }}</td>
    <td>{{ $row->client?->name }}</td>
    <td>{{ money($row->total) }}</td>
    <td>{!! status_badge($row->status) !!}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('quotations.show', $row),
            'edit' => $row->status === 'Converted' ? null : route('quotations.edit', $row),
            'pdf' => route('quotations.pdf', $row),
            'delete' => route('quotations.destroy', $row),
            'confirm' => 'Delete quotation '.$row->quotation_number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="6">None</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $quotations->links() }}</div></div>
@endsection
