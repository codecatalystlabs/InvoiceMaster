@extends('layouts.app')
@section('title', 'Receivables')
@section('content')
<div class="row g-3 mb-3">
    @foreach($totals as $label => $value)
        <div class="col"><div class="stat-card"><h6>{{ $label }}</h6><h3>{{ money($value) }}</h3></div></div>
    @endforeach
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Invoice</th><th>Client</th><th>Due</th><th>Bucket</th><th class="text-end">Outstanding</th><th></th></tr></thead>
<tbody>
@forelse($open as $row)
<tr>
    <td><a href="{{ route('invoices.show', $row['inv']) }}">{{ $row['inv']->invoice_number }}</a></td>
    <td>{{ $row['inv']->displayClient() }}</td>
    <td>{{ $row['inv']->due_date?->format('d M Y') ?: '—' }}</td>
    <td>{{ $row['bucket'] }}</td>
    <td class="text-end">{{ money($row['outstanding']) }}</td>
    <td class="text-nowrap">
        <a class="btn btn-sm btn-outline-primary" href="{{ $row['inv']->payUrl() }}" target="_blank">Pay link</a>
        <form method="POST" action="{{ route('receivables.remind', $row['inv']) }}" class="d-inline">@csrf<button class="btn btn-sm btn-outline-success">Remind</button></form>
    </td>
</tr>
@empty<tr><td colspan="6">No open invoices.</td></tr>@endforelse
</tbody></table></div></div>
@endsection
