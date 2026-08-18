@extends('layouts.public')
@section('title', 'Payment received')
@section('content')
<div class="card"><div class="card-body text-center">
    <h1 class="h4">Thank you</h1>
    <p>Payment for {{ $invoice->invoice_number }} is recorded.</p>
    <p>Paid {{ money($invoice->amount_paid, $invoice->company) }} of {{ money($invoice->total, $invoice->company) }}.</p>
    @if($invoice->outstanding() > 0)
        <p>Balance due {{ money($invoice->outstanding(), $invoice->company) }}.</p>
        <a class="btn btn-primary" href="{{ route('pay.show', $invoice->pay_token) }}">Pay the rest</a>
    @else
        <a class="btn btn-outline-primary" href="{{ route('pay.show', $invoice->pay_token) }}">Back to invoice</a>
    @endif
</div></div>
@endsection
