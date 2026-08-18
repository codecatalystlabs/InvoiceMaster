@extends('layouts.app')
@section('title', $fund->name)
@section('content')
<div class="d-flex justify-content-between mb-3"><a href="{{ route('petty-cash.index') }}" class="btn btn-secondary">Back</a></div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Balance</h6><h3>{{ money($fund->balance) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Float limit</h6><h3>{{ money($fund->float_limit) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Headroom</h6><h3>{{ money($fund->headroom()) }}</h3></div></div>
</div>
<form method="POST" action="{{ route('petty-cash.topup', $fund) }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-3"><select name="type" class="form-select"><option value="allocation">Allocation from budget</option><option value="replenish">Replenish</option></select></div>
    <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
    <div class="col-md-4"><input name="description" class="form-control" placeholder="Description" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Top up</button></div>
</form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>#</th><th>Date</th><th>Type</th><th>Detail</th><th>Amount</th><th>Balance</th></tr></thead>
<tbody>
@forelse($entries as $e)
<tr>
    <td>{{ $e->number }}</td>
    <td>{{ $e->entry_date?->format('d M Y') }}</td>
    <td>{{ $e->type }}</td>
    <td>{{ $e->description }}</td>
    <td>{{ money($e->amount) }}</td>
    <td>{{ money($e->balance_after) }}</td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No movements yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $entries->links() }}</div></div>
@endsection
