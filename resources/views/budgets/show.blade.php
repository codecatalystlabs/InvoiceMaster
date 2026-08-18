@extends('layouts.app')
@section('title', $budget->title)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('budgets.index', ['year'=>$budget->year]) }}" class="btn btn-secondary">Back</a>
    @if($budget->status === 'draft')
        <form method="POST" action="{{ route('budgets.approve', $budget) }}">@csrf<button class="btn btn-success">Approve budget</button></form>
    @endif
</div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Budget</h6><h3>{{ money($budget->amount) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Allocated</h6><h3>{{ money($budget->allocatedTotal()) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Left to allocate</h6><h3>{{ money($budget->remainingToAllocate()) }}</h3></div></div>
</div>
<p>{{ $budget->department?->name }} · {{ $budget->year }} · {!! status_badge($budget->status) !!}</p>
@if($budget->status === 'approved')
<form method="POST" action="{{ route('budgets.allocate', $budget) }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-3"><input name="name" class="form-control" placeholder="Line name" required></div>
    <div class="col-md-3"><select name="category" class="form-select">@foreach(\App\Models\BudgetAllocation::categories() as $k=>$v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></div>
    <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
    <div class="col-md-3"><button class="btn btn-primary w-100">Add allocation</button></div>
</form>
@endif
<div class="card"><table class="table mb-0"><thead><tr><th>Line</th><th>Category</th><th>Allocated</th><th>Committed</th><th>Spent</th><th>Available</th><th></th></tr></thead><tbody>
@forelse($budget->allocations as $line)
<tr>
    <td>{{ $line->name }}</td>
    <td>{{ \App\Models\BudgetAllocation::categories()[$line->category] ?? $line->category }}</td>
    <td>{{ money($line->amount) }}</td>
    <td>{{ money($line->committed()) }}</td>
    <td>{{ money($line->spent()) }}</td>
    <td>{{ money($line->available()) }}</td>
    <td>
        @if($budget->status === 'approved' && $line->requisitions->isEmpty() && $line->topups->isEmpty())
            <form method="POST" action="{{ route('budgets.allocations.destroy', $line) }}" data-confirm="Remove this unused allocation?">@csrf @method('DELETE')
                <button class="btn btn-sm btn-outline-danger">Remove</button>
            </form>
        @endif
    </td>
</tr>
@empty<tr><td colspan="7" class="text-muted">No allocations yet.</td></tr>@endforelse
</tbody></table></div>
<p class="small text-muted mt-2 mb-0">Petty cash lines fund the tin (top-up). Other lines cap staff requisitions. Committed = approved or in-flight requests. Spent = closed accountability or tin top-ups.</p>
@endsection
