@extends('layouts.app')
@section('title', $expense->exists ? 'Edit expense' : 'New expense')
@section('content')
<form method="POST" action="{{ $expense->exists ? route('expenses.update',$expense) : route('expenses.store') }}" class="card"><div class="card-body">
@csrf @if($expense->exists) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="expense_date" class="form-control" value="{{ old('expense_date', optional($expense->expense_date)->toDateString() ?? date('Y-m-d')) }}"></div>
    <div class="col-md-4"><label class="form-label">Vendor</label><input name="vendor_name" class="form-control" value="{{ old('vendor_name',$expense->vendor_name) }}" required></div>
    <div class="col-md-4"><label class="form-label">Category</label><input name="category" class="form-control" value="{{ old('category',$expense->category) }}" required></div>
    <div class="col-md-4"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount',$expense->amount) }}" required></div>
    <div class="col-md-4"><label class="form-label">Method</label><select name="payment_method" class="form-select">@foreach(['Cash','Bank Transfer','Mobile Money','Cheque','Card'] as $m)<option @selected(old('payment_method',$expense->payment_method)==$m)>{{ $m }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Status</label><select name="payment_status" class="form-select">@foreach(['Pending','Paid','Partially Paid','Overdue'] as $s)<option @selected(old('payment_status',$expense->payment_status)==$s)>{{ $s }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Account</label><select name="account_id" class="form-select"><option value="">N/A</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected(old('account_id',$expense->account_id)==$a->id)>{{ $a->account_name }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description',$expense->description) }}"></div>
    <div class="col-12 form-check"><input type="checkbox" name="is_recurring" value="1" class="form-check-input" @checked($expense->is_recurring)><label class="form-check-label">Recurring</label></div>
    <div class="col-md-4"><label class="form-label">Frequency</label><select name="recurrence_frequency" class="form-select"><option value="">—</option>@foreach(['Daily','Weekly','Monthly','Quarterly','Yearly'] as $f)<option @selected($expense->recurrence_frequency==$f)>{{ $f }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Next date</label><input type="date" name="next_recurrence_date" class="form-control" value="{{ optional($expense->next_recurrence_date)->toDateString() }}"></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
</div></form>
@endsection
