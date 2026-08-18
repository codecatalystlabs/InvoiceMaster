@extends('layouts.app')
@section('title', 'Cash Book')
@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Current balance</h6><h3>{{ money($balance) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Total debit</h6><h3 class="text-success">{{ money($in) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Total credit</h6><h3 class="text-danger">{{ money($out) }}</h3></div></div>
</div>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <form class="d-flex gap-2">
        <input type="date" name="from" value="{{ $from }}" class="form-control">
        <input type="date" name="to" value="{{ $to }}" class="form-control">
        <input name="q" value="{{ $q }}" class="form-control" placeholder="Search particulars or number">
        <button class="btn btn-primary">Filter</button>
    </form>
    <div class="d-flex gap-2">
        <a href="{{ route('exports.download','cashbook') }}" class="btn btn-outline-success">CSV</a>
        <a href="{{ route('cashbook.create') }}" class="btn btn-primary">New entry</a>
    </div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead>
<tr>
    <th>No.</th><th>Date</th><th>Particulars</th><th>Folio</th>
    <th>Discount Allowed</th><th>Debit</th><th>Credit</th><th>Balance</th><th>Actions</th>
</tr>
</thead>
<tbody>
@forelse($entries as $e)
<tr>
    <td><a href="{{ route('cashbook.show', $e) }}">{{ $e->number }}</a></td>
    <td>{{ $e->entry_date?->format('d M Y') }}</td>
    <td>{{ $e->description }}</td>
    <td>{{ $e->folio }}</td>
    <td>{{ (float) $e->discount_allowed > 0 ? money($e->discount_allowed) : '' }}</td>
    <td class="text-success">{{ $e->type === 'debit' ? money($e->amount) : '' }}</td>
    <td class="text-danger">{{ $e->type === 'credit' ? money($e->amount) : '' }}</td>
    <td>{{ money($e->balance_after) }}</td>
    <td class="text-nowrap">
        @include('partials.row-actions', [
            'view' => route('cashbook.show', $e),
            'edit' => route('cashbook.edit', $e),
            'pdf' => route('cashbook.pdf', $e),
            'delete' => route('cashbook.destroy', $e),
            'confirm' => 'Delete cash book entry '.$e->number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="9">No cash book entries yet.</td></tr>@endforelse
</tbody>
</table></div><div class="card-body">{{ $entries->links() }}</div></div>
@endsection
