@extends('layouts.app')
@section('title', 'Expenses')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h5 class="mb-0">Total {{ money($total) }}</h5>
    <div class="d-flex gap-2"><a href="{{ route('exports.download','expenses') }}" class="btn btn-outline-success">CSV</a><a href="{{ route('expenses.create') }}" class="btn btn-primary">Add expense</a></div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>#</th><th>Date</th><th>Vendor</th><th>Category</th><th>Amount</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($expenses as $e)
<tr>
    <td><a href="{{ route('expenses.show',$e) }}">{{ $e->expense_number }}</a></td>
    <td>{{ $e->expense_date?->format('d M Y') }}</td>
    <td>{{ $e->vendor_name }}</td>
    <td>{{ $e->category }}</td>
    <td>{{ money($e->amount) }}</td>
    <td>{!! status_badge($e->payment_status) !!}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('expenses.show', $e),
            'edit' => route('expenses.edit', $e),
            'delete' => route('expenses.destroy', $e),
            'confirm' => 'Delete expense '.$e->expense_number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="7">None</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $expenses->links() }}</div></div>
@endsection
