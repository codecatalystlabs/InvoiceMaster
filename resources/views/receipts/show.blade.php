@extends('layouts.app')
@section('title', $receipt->number)
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('receipts.pdf', $receipt) }}" class="btn btn-outline-danger">Download PDF</a>
        <a href="{{ route('receipts.docx', $receipt) }}" class="btn btn-outline-primary">Download Word</a>
        <a href="{{ route('receipts.email', $receipt) }}" class="btn btn-outline-success">Email</a>
        <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-primary">Edit</a>
        <form method="POST" action="{{ route('receipts.destroy', $receipt) }}" onsubmit="return confirm('Delete this receipt?')">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<iframe class="pdf-frame" src="{{ route('receipts.pdf', $receipt) }}?inline=1&t={{ optional($receipt->updated_at)->timestamp }}" title="Receipt {{ $receipt->number }}"></iframe>
@endsection
