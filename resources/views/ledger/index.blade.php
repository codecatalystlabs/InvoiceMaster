@extends('layouts.app')
@section('title', 'General Ledger')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4 mb-0">General Ledger</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('ledger.preview', request()->query()) }}" class="btn btn-outline-primary">View ledger sheet</a>
        <a href="{{ route('ledger.pdf', request()->query()) }}" class="btn btn-outline-danger">Download PDF</a>
        <a href="{{ route('exports.download','ledger') }}" class="btn btn-outline-success">CSV</a>
        <form method="POST" action="{{ route('ledger.rebuild') }}" data-confirm="Rebuild the ledger from invoices, receipts, expenses, and cash book?">
            @csrf
            <button class="btn btn-outline-secondary">Rebuild ledger</button>
        </form>
        <button type="button" class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>
</div>
<div class="card mb-3 no-print"><div class="card-body">
    <form class="row g-3">
        <div class="col-md-3"><label class="form-label">From</label><input type="date" name="from" class="form-control" value="{{ $from }}"></div>
        <div class="col-md-3"><label class="form-label">To</label><input type="date" name="to" class="form-control" value="{{ $to }}"></div>
        <div class="col-md-4"><label class="form-label">Account</label>
            <select name="account_id" class="form-select">
                <option value="0">All accounts</option>
                @foreach($accounts as $a)
                    <option value="{{ $a->id }}" @selected($accountId==$a->id)>{{ $a->account_code }} - {{ $a->account_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 d-flex align-items-end"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
</div></div>
<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="stat-card"><h6>Total debits</h6><h3>{{ money($debit) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Total credits</h6><h3>{{ money($credit) }}</h3></div></div>
    <div class="col-md-4"><div class="stat-card"><h6>Balance</h6><h3 class="{{ ($debit-$credit) >= 0 ? 'text-success' : 'text-danger' }}">{{ money(abs($debit-$credit)) }}</h3><small class="text-muted">{{ ($debit-$credit) >= 0 ? 'Debit' : 'Credit' }}</small></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Date</th><th>Reference</th><th>Account</th><th>Description</th><th>Source</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
<tbody>
@forelse($entries as $e)
<tr>
    <td>{{ $e->entry_date?->format('d M Y') }}</td>
    <td><code>{{ $e->reference_number }}</code></td>
    <td>{{ $e->account?->account_code }} {{ $e->account?->account_name }}</td>
    <td>{{ $e->description }}</td>
    <td>{{ $e->source_type }}</td>
    <td class="text-end">{{ $e->entry_type === 'Debit' ? money($e->amount) : '—' }}</td>
    <td class="text-end">{{ $e->entry_type === 'Credit' ? money($e->amount) : '—' }}</td>
</tr>
@empty
<tr><td colspan="7" class="text-muted">No ledger entries found for the selected period.</td></tr>
@endforelse
@if($entries->count())
<tr class="table-light">
    <td colspan="5" class="text-end"><strong>Totals</strong></td>
    <td class="text-end"><strong>{{ money($debit) }}</strong></td>
    <td class="text-end"><strong>{{ money($credit) }}</strong></td>
</tr>
@endif
</tbody></table></div><div class="card-body no-print">{{ $entries->links() }}</div></div>
@endsection
