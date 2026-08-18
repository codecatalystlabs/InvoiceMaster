@extends('layouts.app')
@section('title', 'Cash Book '.$entry->number)
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('cashbook.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('cashbook.pdf', $entry) }}" class="btn btn-outline-danger">Download PDF</a>
        <a href="{{ route('cashbook.edit', $entry) }}" class="btn btn-primary">Edit</a>
        <form method="POST" action="{{ route('cashbook.destroy', $entry) }}" onsubmit="return confirm('Delete this cash book entry?')">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<iframe class="pdf-frame" src="{{ route('cashbook.pdf', $entry) }}?inline=1&t={{ optional($entry->updated_at)->timestamp }}" title="Cash book {{ $entry->number }}"></iframe>
@endsection
