@extends('layouts.app')
@section('title', $entry->exists ? 'Edit cash entry' : 'New cash entry')
@section('content')
<form method="POST" action="{{ $entry->exists ? route('cashbook.update',$entry) : route('cashbook.store') }}" class="card"><div class="card-body">
@csrf @if($entry->exists) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="entry_date" class="form-control" value="{{ old('entry_date', optional($entry->entry_date)->toDateString() ?? date('Y-m-d')) }}"></div>
    <div class="col-md-4"><label class="form-label">Type</label>
        <select name="type" class="form-select">
            <option value="debit" @selected(old('type',$entry->type)==='debit')>Debit (cash in)</option>
            <option value="credit" @selected(old('type',$entry->type)==='credit')>Credit (cash out)</option>
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" value="{{ old('amount',$entry->amount) }}" required></div>
    <div class="col-md-8"><label class="form-label">Particulars</label><input name="description" class="form-control" value="{{ old('description',$entry->description) }}" required></div>
    <div class="col-md-4"><label class="form-label">Folio</label><input name="folio" class="form-control" value="{{ old('folio',$entry->folio) }}"></div>
    <div class="col-md-4"><label class="form-label">Discount allowed</label><input type="number" step="0.01" name="discount_allowed" class="form-control" value="{{ old('discount_allowed',$entry->discount_allowed) }}"></div>
    <div class="col-md-4"><label class="form-label">Account</label>
        <select name="account_id" class="form-select"><option value="">None</option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected(old('account_id',$entry->account_id)==$a->id)>{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select>
    </div>
    <div class="col-md-4"><label class="form-label">Payment method</label><input name="payment_method" class="form-control" value="{{ old('payment_method',$entry->payment_method) }}"></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
</div></form>
@endsection
