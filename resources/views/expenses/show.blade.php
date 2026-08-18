@extends('layouts.app')
@section('title', $expense->expense_number)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('expenses.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2">
        <a href="{{ route('expenses.edit',$expense) }}" class="btn btn-primary">Edit</a>
        <form method="POST" action="{{ route('expenses.destroy', $expense) }}" data-confirm="Delete expense {{ $expense->expense_number }}? This cannot be undone.">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<div class="card"><div class="card-body">
    <p><strong>Vendor:</strong> {{ $expense->vendor_name }}</p>
    <p><strong>Amount:</strong> {{ money($expense->amount) }} · {!! status_badge($expense->payment_status) !!}</p>
    <p><strong>Category:</strong> {{ $expense->category }} · <strong>Account:</strong> {{ $expense->account?->account_name ?? 'N/A' }}</p>
    <p>{{ $expense->description }}</p>
</div></div>
@endsection
