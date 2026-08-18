@extends('layouts.app')
@section('title', 'Annual budgets')
@section('content')
<form class="d-flex gap-2 mb-3"><input type="number" name="year" value="{{ $year }}" class="form-control" style="max-width:120px"><button class="btn btn-primary">View year</button></form>
<form method="POST" action="{{ route('budgets.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-3"><select name="department_id" class="form-select" required><option value="">Department</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
    <div class="col-md-2"><input type="number" name="year" class="form-control" value="{{ $year }}" required></div>
    <div class="col-md-3"><input name="title" class="form-control" placeholder="Title" value="{{ $year }} budget" required></div>
    <div class="col-md-2"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Draft budget</button></div>
</form>
<div class="card"><table class="table mb-0"><thead><tr><th>Department</th><th>Year</th><th>Amount</th><th>Allocated</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($budgets as $b)
<tr>
    <td>{{ $b->department?->name }}</td>
    <td>{{ $b->year }}</td>
    <td>{{ money($b->amount) }}</td>
    <td>{{ money($b->allocatedTotal()) }}</td>
    <td>{!! status_badge($b->status) !!}</td>
    <td><a href="{{ route('budgets.show', $b) }}" class="btn btn-sm btn-outline-secondary">Open</a></td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No budgets for {{ $year }}.</td></tr>@endforelse
</tbody></table></div>
@endsection
