@extends('layouts.public')
@section('title', 'Your invoices')
@section('content')
<h1 class="h4 mb-3">{{ $client->name }}</h1>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Invoice</th><th>Date</th><th>Status</th><th class="text-end">Total</th><th></th></tr></thead>
<tbody>
@forelse($invoices as $inv)
<tr>
    <td>{{ $inv->invoice_number }}</td>
    <td>{{ $inv->date?->format('d M Y') }}</td>
    <td>{{ $inv->status }}</td>
    <td class="text-end">{{ money($inv->total, $company) }}</td>
    <td>@if($inv->isOpen())<a href="{{ $inv->payUrl() }}">Pay</a>@endif</td>
</tr>
@empty<tr><td colspan="5">No invoices.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
