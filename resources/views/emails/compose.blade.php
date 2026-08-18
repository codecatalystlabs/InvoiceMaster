@extends('layouts.app')
@section('title', $reply ? 'Reply' : 'Compose email')
@section('content')
@if($reply)
<div class="card mb-3 bg-light"><div class="card-body">
    <h6 class="mb-1">Replying to</h6>
    <p class="mb-1"><strong>From:</strong> {{ $reply->from_email }}</p>
    <p class="mb-1"><strong>Subject:</strong> {{ $reply->subject }}</p>
    <p class="mb-0 text-muted small">{{ $reply->sent_at?->format('d M Y H:i') }}</p>
</div></div>
@endif
<div class="card" style="max-width:720px">
    <div class="card-body">
        <form method="POST" action="{{ route('emails.send') }}">@csrf
            @if($reply)<input type="hidden" name="reply_to_id" value="{{ $reply->id }}">@endif
            <div class="mb-3">
                <label class="form-label">To</label>
                <input type="email" name="to" class="form-control" value="{{ old('to', $reply?->from_email) }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">CC</label>
                <input name="cc" class="form-control" value="{{ old('cc') }}" placeholder="optional, comma-separated">
            </div>
            <div class="mb-3">
                <label class="form-label">BCC</label>
                <input name="bcc" class="form-control" value="{{ old('bcc') }}" placeholder="optional, comma-separated">
            </div>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input name="subject" class="form-control" value="{{ old('subject', $reply ? 'Re: '.$reply->subject : '') }}" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="10" required>{{ old('message') }}</textarea>
            </div>
            @if($reply)
                <div class="mb-3 p-3 bg-light rounded small">
                    <strong>Original message</strong>
                    <div class="mt-2">{!! $reply->safeHtml() !!}</div>
                </div>
            @endif
            <div class="d-flex gap-2">
                <button class="btn btn-primary">Send</button>
                <a href="{{ route('emails.index') }}" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
