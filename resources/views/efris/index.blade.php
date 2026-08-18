@extends('layouts.app')
@section('title', 'URA EFRIS')
@section('content')
<p class="text-muted">Tax invoices are queued here until a URA device number is saved in Settings. The client PDF remains your letterhead copy.</p>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Invoice</th><th>Status</th><th>FDN</th><th>Note</th></tr></thead>
<tbody>
@forelse($rows as $row)
<tr>
    <td>{{ $row->invoice?->invoice_number }}</td>
    <td>{!! status_badge($row->status) !!}</td>
    <td>{{ $row->fdn ?: '—' }}</td>
    <td class="small">{{ $row->error_message }}</td>
</tr>
@empty<tr><td colspan="4">No submissions yet. Queue one from an invoice.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $rows->links() }}</div></div>
@endsection
