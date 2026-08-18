@extends('layouts.app')
@section('title', $receipt->number)
@push('head')
<link rel="stylesheet" href="{{ asset('css/receipt-sheet.css') }}">
@endpush
@section('content')
<div class="rcpt-preview">
    <div class="d-flex justify-content-between mb-3 flex-wrap gap-2 no-print">
        <a href="{{ route('receipts.index') }}" class="btn btn-secondary">Back</a>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('receipts.pdf', $receipt) }}" class="btn btn-outline-danger">Download PDF</a>
            <a href="{{ route('receipts.docx', $receipt) }}" class="btn btn-outline-primary">Download Word</a>
            <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
            <a href="{{ route('receipts.email', $receipt) }}" class="btn btn-outline-success">Email</a>
            <a href="{{ route('receipts.edit', $receipt) }}" class="btn btn-primary">Edit</a>
            <form method="POST" action="{{ route('receipts.destroy', $receipt) }}" data-confirm="Delete receipt {{ $receipt->number }}? This cannot be undone.">@csrf @method('DELETE')
                <button class="btn btn-outline-danger">Delete</button>
            </form>
        </div>
    </div>
    @include('receipts.partials.sheet')
</div>
@endsection
@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('sheetWrap-{{ $receipt->id }}');
    var sheet = document.getElementById('printable-{{ $receipt->id }}');
    if (!wrap || !sheet) return;
    function fit() {
        sheet.style.transform = 'none';
        var w = sheet.offsetWidth;
        var scale = Math.min(1, wrap.clientWidth / w);
        sheet.style.transform = 'scale(' + scale + ')';
        wrap.style.height = (sheet.offsetHeight * scale) + 'px';
    }
    window.addEventListener('resize', fit);
    var logo = sheet.querySelector('.logo-img');
    if (logo && !logo.complete) logo.addEventListener('load', fit);
    fit();
})();
</script>
@endpush
