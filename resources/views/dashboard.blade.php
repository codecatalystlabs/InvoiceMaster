@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="hero-card mb-3">
    <div>
        <h2>Dashboard</h2>
        <p>Overview of invoices, quotations, receipts, and accounts for {{ auth()->user()->company->name ?? config('app.name') }}.</p>
    </div>
    <div class="signed-box">
        <img src="{{ auth()->user()->company?->logoUrl() ?? asset('images/logo.png') }}" alt="logo">
        <div>
            <div class="who">Signed in as</div>
            <div class="name">{{ auth()->user()->name }}</div>
            <div class="who">{{ auth()->user()->role }}</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-1">
    <div class="col-md-4">
        <div class="info-tile">
            <div class="info-tile-head"><i class="bi bi-receipt"></i> Invoices</div>
            <div class="info-tile-body">
                <strong>{{ $stats['invoices'] }}</strong>
                {{ $stats['unpaid'] }} unpaid · {{ money($stats['pending']) }} outstanding
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-tile">
            <div class="info-tile-head"><i class="bi bi-cash-coin"></i> Collections</div>
            <div class="info-tile-body">
                <strong>{{ money($stats['receipts_total']) }}</strong>
                {{ $stats['receipts'] }} receipts recorded
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-tile">
            <div class="info-tile-head"><i class="bi bi-graph-up-arrow"></i> Paid revenue</div>
            <div class="info-tile-body">
                <strong>{{ money($stats['revenue']) }}</strong>
                Cash book {{ money($stats['cash_balance']) }}
            </div>
        </div>
    </div>
</div>

<div class="section-kicker">Priority work</div>
<div class="section-title">Quick actions</div>
<div class="row g-3 mb-3">
    @if(can_module('canteen'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile featured" href="{{ route('canteen.today') }}">
            <div class="action-icon"><i class="bi bi-cup-hot"></i></div>
            <h6>Log a meal</h6>
            <p>Optional. Pay for the source; foods served with it are included.</p>
        </a>
    </div>
    @endif
    @if(can_module('invoices'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('invoices.create') }}">
            <div class="action-icon"><i class="bi bi-plus-lg"></i></div>
            <h6>New invoice</h6>
            <p>Bill a client and send the PDF.</p>
        </a>
    </div>
    @endif
    @if(can_module('quotations'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('quotations.create') }}">
            <div class="action-icon"><i class="bi bi-file-earmark-plus"></i></div>
            <h6>New quotation</h6>
            <p>Price work before you invoice.</p>
        </a>
    </div>
    @endif
    @if(can_module('receipts'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('receipts.create') }}">
            <div class="action-icon"><i class="bi bi-ticket-perforated"></i></div>
            <h6>Record receipt</h6>
            <p>Capture a payment against an invoice.</p>
        </a>
    </div>
    @endif
    @if(can_module('clients'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('clients.index') }}">
            <div class="action-icon"><i class="bi bi-person-plus"></i></div>
            <h6>Add client</h6>
            <p>Keep company and contact details here.</p>
        </a>
    </div>
    @endif
    @if(can_module('emails'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('emails.compose') }}">
            <div class="action-icon"><i class="bi bi-envelope"></i></div>
            <h6>Compose email</h6>
            <p>Write or reply from the company inbox.</p>
        </a>
    </div>
    @endif
    @if(can_module('cashbook'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('cashbook.create') }}">
            <div class="action-icon"><i class="bi bi-journal-plus"></i></div>
            <h6>Cash book entry</h6>
            <p>Post a debit or credit to the book.</p>
        </a>
    </div>
    @endif
    @if(can_module('expenses'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('expenses.create') }}">
            <div class="action-icon"><i class="bi bi-receipt-cutoff"></i></div>
            <h6>Log expense</h6>
            <p>Record a cost against an account.</p>
        </a>
    </div>
    @endif
    @if(can_module('reports'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('reports.financial') }}">
            <div class="action-icon"><i class="bi bi-bar-chart"></i></div>
            <h6>Financial reports</h6>
            <p>Income, expenses, and period totals.</p>
        </a>
    </div>
    @endif
    @if(can_module('canteen.review'))
    <div class="col-md-3 col-sm-6">
        <a class="action-tile" href="{{ route('canteen.review') }}">
            <div class="action-icon"><i class="bi bi-check2-square"></i></div>
            <h6>Review meals</h6>
            <p>{{ $stats['canteen_pending'] ?? 0 }} waiting · month {{ money($stats['canteen_month'] ?? 0) }}</p>
        </a>
    </div>
    @endif
</div>

<div class="section-kicker">Operations</div>
<div class="section-title">Modules</div>
@if(can_module('canteen'))
<div class="module-row">
    <div>
        <h6>Canteen</h6>
        <p>{{ money($stats['canteen_month'] ?? 0) }} approved this month · {{ $stats['canteen_pending'] ?? 0 }} pending review</p>
    </div>
    <a href="{{ route('canteen.index') }}">Open</a>
</div>
@endif
@if(can_module('invoices'))
<div class="module-row">
    <div>
        <h6>Invoices</h6>
        <p>{{ $stats['invoices'] }} on file · {{ $stats['unpaid'] }} still unpaid</p>
    </div>
    <a href="{{ route('invoices.index') }}">Open</a>
</div>
@endif
@if(can_module('quotations'))
<div class="module-row">
    <div>
        <h6>Quotations</h6>
        <p>{{ $stats['quotations'] }} quotations</p>
    </div>
    <a href="{{ route('quotations.index') }}">Open</a>
</div>
@endif
@if(can_module('receipts'))
<div class="module-row">
    <div>
        <h6>Receipts</h6>
        <p>{{ $stats['receipts'] }} receipts · {{ money($stats['receipts_total']) }}</p>
    </div>
    <a href="{{ route('receipts.index') }}">Open</a>
</div>
@endif
@if(can_module('cashbook'))
<div class="module-row">
    <div>
        <h6>Cash book</h6>
        <p>Balance {{ money($stats['cash_balance']) }}</p>
    </div>
    <a href="{{ route('cashbook.index') }}">Open</a>
</div>
@endif
@if(can_module('ledger'))
<div class="module-row">
    <div>
        <h6>Ledger</h6>
        <p>This month's expenses {{ money($stats['expenses_month']) }}</p>
    </div>
    <a href="{{ route('ledger.index') }}">Open</a>
</div>
@endif

<div class="row g-3 mt-2">
    <div class="col-md-6">
        <div class="card"><div class="card-header">Recent invoices</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>#</th><th>Client</th><th>Total</th><th></th></tr></thead><tbody>
        @forelse($recentInvoices as $row)
            <tr><td><a href="{{ route('invoices.show', $row) }}">{{ $row->invoice_number }}</a></td><td>{{ $row->displayClient() }}</td><td>{{ money($row->total) }}</td><td>{!! status_badge($row->status) !!}</td></tr>
        @empty<tr><td colspan="4" class="text-muted">None yet</td></tr>@endforelse
        </tbody></table></div></div>
    </div>
    <div class="col-md-6">
        <div class="card"><div class="card-header">Recent receipts</div>
        <div class="table-responsive"><table class="table mb-0"><thead><tr><th>#</th><th>Client</th><th>Amount</th></tr></thead><tbody>
        @forelse($recentReceipts as $row)
            <tr><td><a href="{{ route('receipts.show', $row) }}">{{ $row->number }}</a></td><td>{{ $row->client_name }}</td><td>{{ money($row->amount) }}</td></tr>
        @empty<tr><td colspan="3" class="text-muted">None yet</td></tr>@endforelse
        </tbody></table></div></div>
    </div>
</div>
@endsection
