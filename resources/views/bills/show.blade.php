@extends('layouts.app')
@section('title', $bill->number)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('bills.index') }}" class="btn btn-secondary">Back</a>
</div>
<div class="row g-3">
<div class="col-md-7"><div class="card"><div class="card-body">
    <h2 class="h5">{{ $bill->vendor_name }}</h2>
    <p>{{ $bill->bill_date?->format('d M Y') }} · {!! status_badge($bill->status) !!}</p>
    <table class="table"><thead><tr><th>Item</th><th>Qty</th><th class="text-end">Total</th></tr></thead>
    <tbody>@foreach($bill->items as $i)<tr><td>{{ $i->item_name }}</td><td>{{ $i->qty }}</td><td class="text-end">{{ money($i->total) }}</td></tr>@endforeach</tbody></table>
    <div class="d-flex justify-content-between"><span>Tax</span><strong>{{ money($bill->tax) }}</strong></div>
    <div class="d-flex justify-content-between fs-5"><span>Total</span><strong>{{ money($bill->total) }}</strong></div>
    <div class="d-flex justify-content-between"><span>Outstanding</span><strong>{{ money($bill->outstanding()) }}</strong></div>
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header">Pay bill</div><div class="card-body">
@if($bill->outstanding() > 0)
<form method="POST" action="{{ route('bills.pay', $bill) }}">@csrf
    <input type="number" step="0.01" name="amount" class="form-control mb-2" value="{{ $bill->outstanding() }}" required>
    <select name="method" class="form-select mb-2"><option value="bank">Bank</option><option value="cash">Cash</option><option value="mtn_momo">MTN MoMo</option><option value="airtel_money">Airtel Money</option></select>
    <button class="btn btn-primary">Record payment</button>
</form>
@else
    <p class="text-success mb-0">Paid in full.</p>
@endif
</div></div></div>
</div>
@endsection
