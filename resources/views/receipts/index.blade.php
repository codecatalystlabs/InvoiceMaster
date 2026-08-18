@extends('layouts.app')
@section('title', 'Receipts')
@section('content')
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Receipts</h6><h3>{{ $count }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Total collected</h6><h3>{{ money($total) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Average</h6><h3>{{ money($average) }}</h3></div></div>
</div>
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2"><input name="q" value="{{ $q }}" class="form-control" placeholder="Search by client or number"><button class="btn btn-primary">Search</button></form>
    <div class="d-flex gap-2"><a href="{{ route('exports.download','receipts') }}" class="btn btn-outline-success">CSV</a><a href="{{ route('receipts.create') }}" class="btn btn-primary">New receipt</a></div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>#</th><th>Date</th><th>Received from</th><th>Being</th><th>Amount</th><th>Method</th><th>Actions</th></tr></thead>
<tbody>
@forelse($receipts as $r)
<tr>
    <td><a href="{{ route('receipts.show',$r) }}">{{ $r->number }}</a></td>
    <td>{{ $r->issued_date?->format('d M Y') }}</td>
    <td>{{ $r->client_name }}</td>
    <td>{{ $r->description }}</td>
    <td>{{ money($r->amount) }}</td>
    <td>{{ $r->methodLabel() }}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('receipts.show', $r),
            'edit' => route('receipts.edit', $r),
            'pdf' => route('receipts.pdf', $r),
            'delete' => route('receipts.destroy', $r),
            'confirm' => 'Delete receipt '.$r->number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="7">No receipts yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $receipts->links() }}</div></div>
@endsection
