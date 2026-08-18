@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><h6>Count</h6><h3>{{ $summary['count'] }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Value</h6><h3>{{ money($summary['value']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Overdue</h6><h3>{{ $summary['overdue'] }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Overdue value</h6><h3>{{ money($summary['overdue_value']) }}</h3></div></div>
</div>
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2">
        <input name="q" value="{{ $q }}" class="form-control" placeholder="Search">
        <select name="status" class="form-select">
            <option value="">All</option>
            @foreach(['draft','proforma','sent','Unpaid','Partially Paid','Paid','Overdue','Cancelled'] as $s)
                <option value="{{ $s }}" @selected($status===$s)>{{ $s }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('exports.download', 'invoices') }}" class="btn btn-outline-success">CSV</a>
        <a href="{{ route('invoices.create') }}" class="btn btn-primary">New invoice</a>
    </div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>#</th><th>Date</th><th>Client</th><th>Total</th><th>Status</th><th>Actions</th></tr></thead>
<tbody>
@forelse($invoices as $inv)
<tr>
    <td><a href="{{ route('invoices.show', $inv) }}">{{ $inv->invoice_number }}</a></td>
    <td>{{ $inv->date?->format('d M Y') }}</td>
    <td>{{ $inv->displayClient() }}</td>
    <td>{{ money($inv->total) }}</td>
    <td>{!! status_badge($inv->status) !!}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('invoices.show', $inv),
            'edit' => route('invoices.edit', $inv),
            'pdf' => route('invoices.pdf', $inv),
            'delete' => route('invoices.destroy', $inv),
            'confirm' => 'Delete invoice '.$inv->invoice_number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="6">No invoices</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $invoices->links() }}</div></div>
@endsection
