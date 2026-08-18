@extends('layouts.app')
@section('title', 'Emails')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h2 class="h4 mb-0">Emails</h2>
        <p class="text-muted mb-0">Inbox and sent mail. Unread incoming: <strong>{{ $unread }}</strong></p>
    </div>
    <div class="d-flex gap-2">
        @if(config('imap.enabled'))
            <form method="POST" action="{{ route('emails.sync') }}">@csrf
                <button class="btn btn-info">Check for new emails</button>
            </form>
        @endif
        <a href="{{ route('emails.compose') }}" class="btn btn-primary">Compose</a>
    </div>
</div>
<div class="mb-3 d-flex gap-2">
    <a href="{{ route('emails.index') }}" class="btn btn-sm {{ $direction === null || $direction === '' ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
    <a href="{{ route('emails.index', ['direction' => 'incoming']) }}" class="btn btn-sm {{ $direction === 'incoming' ? 'btn-success' : 'btn-outline-success' }}">Inbox</a>
    <a href="{{ route('emails.index', ['direction' => 'outgoing']) }}" class="btn btn-sm {{ $direction === 'outgoing' ? 'btn-primary' : 'btn-outline-primary' }}">Sent</a>
    <a href="{{ route('emails.index', ['direction' => 'incoming', 'status' => 'received']) }}" class="btn btn-sm {{ $direction === 'incoming' && $status === 'received' ? 'btn-info' : 'btn-outline-info' }}">Unread</a>
</div>
<div class="card mb-3"><div class="card-body">
    <form class="row g-2">
        <div class="col-md-2">
            <select name="direction" class="form-select">
                <option value="">All directions</option>
                <option value="incoming" @selected($direction==='incoming')>Incoming</option>
                <option value="outgoing" @selected($direction==='outgoing')>Outgoing</option>
            </select>
        </div>
        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">All statuses</option>
                @foreach(['sent','received','read','failed'] as $s)
                    <option value="{{ $s }}" @selected($status===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2">
            <select name="reference" class="form-select">
                <option value="">All types</option>
                @foreach(['invoice','quotation','receipt','general'] as $s)
                    <option value="{{ $s }}" @selected($reference===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4"><input name="q" value="{{ $q }}" class="form-control" placeholder="Subject, from, to"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Filter</button></div>
    </form>
</div></div>
<div class="card"><div class="table-responsive">
<table class="table mb-0">
    <thead><tr><th></th><th>Direction</th><th>From / To</th><th>Subject</th><th>Type</th><th>Status</th><th>Date</th></tr></thead>
    <tbody>
    @forelse($emails as $row)
        <tr class="{{ $row->isUnread() ? 'table-info' : '' }}">
            <td>
                @if($row->isIncoming())
                    <i class="bi bi-arrow-down-left text-success"></i>
                @else
                    <i class="bi bi-arrow-up-right text-primary"></i>
                @endif
                @if($row->has_attachment)<i class="bi bi-paperclip"></i>@endif
            </td>
            <td>
                @if($row->isIncoming())
                    <span class="badge bg-success">Incoming</span>
                @else
                    <span class="badge bg-primary">Outgoing</span>
                @endif
            </td>
            <td>
                @if($row->isIncoming())
                    <small class="text-muted">From</small><br>{{ $row->from_name ?: $row->from_email }}
                @else
                    <small class="text-muted">To</small><br>{{ $row->to_email }}
                @endif
            </td>
            <td><a href="{{ route('emails.show', $row) }}">{{ $row->subject }}</a></td>
            <td>{{ $row->reference_type }}</td>
            <td>{!! status_badge($row->status) !!}</td>
            <td>{{ $row->sent_at?->format('d M Y H:i') }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="text-muted">No emails yet. Use Compose to send, or Check for new emails to fetch the inbox.</td></tr>
    @endforelse
    </tbody>
</table>
</div></div>
<div class="mt-3">{{ $emails->links() }}</div>
@endsection
