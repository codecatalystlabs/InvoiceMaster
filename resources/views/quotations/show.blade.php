@extends('layouts.app')
@section('title', $quotation->quotation_number)
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('quotations.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('quotations.pdf', $quotation) }}" class="btn btn-outline-danger">Download PDF</a>
        <a href="{{ route('quotations.email', $quotation) }}" class="btn btn-outline-success">Email</a>
        @if($quotation->status !== 'Converted')
            <a href="{{ route('quotations.edit', $quotation) }}" class="btn btn-primary">Edit</a>
            <form method="POST" action="{{ route('quotations.convert', $quotation) }}">@csrf<button class="btn btn-success">Convert to invoice</button></form>
        @endif
        <form method="POST" action="{{ route('quotations.destroy', $quotation) }}" data-confirm="Delete quotation {{ $quotation->quotation_number }}? This cannot be undone.">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<iframe class="pdf-frame" src="{{ route('quotations.pdf', $quotation) }}?inline=1&t={{ optional($quotation->updated_at)->timestamp }}" title="Quotation {{ $quotation->quotation_number }}"></iframe>
@endsection
