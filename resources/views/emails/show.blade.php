@extends('layouts.app')
@section('title', $email->subject)
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('emails.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2">
        @if($email->isIncoming())
            <a href="{{ route('emails.compose', ['reply_to' => $email->id]) }}" class="btn btn-primary">Reply</a>
        @endif
    </div>
</div>
<div class="card mb-3">
    <div class="card-header d-flex justify-content-between flex-wrap gap-2">
        <div>
            <h5 class="mb-1">{{ $email->subject }}</h5>
            @if($email->isIncoming())
                <span class="badge bg-success">Incoming</span>
            @else
                <span class="badge bg-primary">Outgoing</span>
            @endif
            {!! status_badge($email->status) !!}
            @if($email->reference_type === 'invoice' && $email->reference_id)
                <a href="{{ route('invoices.show', $email->reference_id) }}" class="badge bg-warning text-decoration-none">Invoice</a>
            @elseif($email->reference_type === 'quotation' && $email->reference_id)
                <a href="{{ route('quotations.show', $email->reference_id) }}" class="badge bg-info text-decoration-none">Quotation</a>
            @elseif($email->reference_type === 'receipt' && $email->reference_id)
                <a href="{{ route('receipts.show', $email->reference_id) }}" class="badge bg-secondary text-decoration-none">Receipt</a>
            @endif
        </div>
        <small class="text-muted">{{ $email->sent_at?->format('d M Y H:i') }}</small>
    </div>
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6"><strong>From</strong><br>{{ $email->from_name }} &lt;{{ $email->from_email }}&gt;</div>
            <div class="col-md-6"><strong>To</strong><br>{{ $email->to_email }}</div>
        </div>
        @if($email->cc_email)<p><strong>CC:</strong> {{ $email->cc_email }}</p>@endif
        @if($email->error_message)<div class="alert alert-danger">{{ $email->error_message }}</div>@endif
        @if($email->files->count())
            <div class="alert alert-info mb-3">
                <strong>Attachments</strong>
                <ul class="mb-0">
                    @foreach($email->files as $file)
                        <li><a href="{{ route('emails.attachment', [$email, $file]) }}">{{ $file->filename }}</a></li>
                    @endforeach
                </ul>
            </div>
        @elseif($email->has_attachment && $email->attachment_name)
            <div class="alert alert-info">Attachment: {{ $email->attachment_name }}</div>
        @endif
        <hr>
        <div class="p-3 rounded" style="background:#f8f9fa">{!! $email->safeHtml() !!}</div>
    </div>
    @if($email->sender)
        <div class="card-footer text-muted small">Sent by {{ $email->sender->name }}</div>
    @endif
</div>
@if($thread->count())
<div class="card">
    <div class="card-header">Conversation ({{ $thread->count() }})</div>
    <div class="card-body">
        @foreach($thread as $item)
            <div class="border-start border-3 ps-3 mb-3 {{ $item->isIncoming() ? 'border-success' : 'border-primary' }}">
                <div class="d-flex justify-content-between">
                    <strong>{{ $item->isIncoming() ? 'From '.$item->displayParty() : 'To '.$item->to_email }}</strong>
                    <small class="text-muted">{{ $item->sent_at?->format('d M Y H:i') }}</small>
                </div>
                <p class="mb-1">{{ $item->subject }}</p>
                <p class="mb-0 small">{{ Str::limit(strip_tags($item->body_html), 200) }}
                    <a href="{{ route('emails.show', $item) }}">Read</a>
                </p>
            </div>
        @endforeach
    </div>
</div>
@endif
@endsection
