@extends('layouts.app')
@section('title', 'Cash Book '.$entry->number)
@push('head')
<link rel="stylesheet" href="{{ asset('css/cashbook-sheet.css') }}">
@endpush
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 no-print">
    <a href="{{ route('cashbook.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('cashbook.pdf', $entry) }}" class="btn btn-outline-danger">Download PDF</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
        <a href="{{ route('cashbook.edit', $entry) }}" class="btn btn-primary">Edit</a>
        <form method="POST" action="{{ route('cashbook.destroy', $entry) }}" data-confirm="Delete cash book entry {{ $entry->number }}? This cannot be undone.">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<div class="cb-preview">
    @include('cashbook.partials.sheet')
</div>
@endsection
@push('scripts')
<script>
(function () {
    var wrap = document.getElementById('cbWrap-{{ $entry->id }}');
    var sheet = document.getElementById('cbSheet-{{ $entry->id }}');
    if (!wrap || !sheet) return;
    function fit() {
        sheet.style.transform = 'none';
                    var scale = Math.min(1, wrap.clientWidth / sheet.offsetWidth);
        sheet.style.transform = 'scale(' + scale + ')';
        wrap.style.height = (sheet.offsetHeight * scale) + 'px';
    }
    window.addEventListener('resize', fit);
    var logo = sheet.querySelector('img');
    if (logo && !logo.complete) logo.addEventListener('load', fit);
    fit();
})();
</script>
@endpush
