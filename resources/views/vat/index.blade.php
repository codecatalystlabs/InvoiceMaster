@extends('layouts.app')
@section('title', 'VAT worksheet')
@section('content')
<form class="card card-body mb-3 row g-2" method="GET">
    <div class="col-md-4"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-md-4"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Generate</button></div>
</form>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><h6>Taxable sales</h6><h3>{{ money($sheet['sales']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Output VAT</h6><h3>{{ money($sheet['output']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Input VAT</h6><h3>{{ money($sheet['input']) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Net VAT</h6><h3>{{ money($sheet['net']) }}</h3></div></div>
</div>
<div class="card"><div class="card-header">Output invoices</div><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Invoice</th><th>Date</th><th class="text-end">Total</th><th class="text-end">VAT</th></tr></thead>
<tbody>
@foreach($sheet['invoices'] as $inv)
<tr><td>{{ $inv->invoice_number }}</td><td>{{ $inv->date?->format('d M Y') }}</td><td class="text-end">{{ money($inv->total) }}</td><td class="text-end">{{ money($inv->tax) }}</td></tr>
@endforeach
</tbody></table></div></div>
<p class="text-muted small mt-2">Output VAT comes from invoice tax. Input VAT comes from expense and bill tax fields. Set the company tax rate in Settings (used as the default on new invoices).</p>
@endsection
