@extends('layouts.app')
@section('title', 'Purchase bills')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4 mb-0">Purchase bills</h2>
    <a href="{{ route('bills.create') }}" class="btn btn-primary">New bill</a>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>No.</th><th>Vendor</th><th>Date</th><th class="text-end">Total</th><th>Status</th></tr></thead>
<tbody>
@forelse($bills as $b)
<tr>
    <td><a href="{{ route('bills.show', $b) }}">{{ $b->number }}</a></td>
    <td>{{ $b->vendor_name }}</td>
    <td>{{ $b->bill_date?->format('d M Y') }}</td>
    <td class="text-end">{{ money($b->total) }}</td>
    <td>{!! status_badge($b->status) !!}</td>
</tr>
@empty<tr><td colspan="5">No bills yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $bills->links() }}</div></div>
@endsection
