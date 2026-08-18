@extends('layouts.app')
@section('title', $title)
@push('head')
<link rel="stylesheet" href="{{ asset('css/cashbook-sheet.css') }}">
@endpush
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2 no-print">
    <a href="{{ route('ledger.index', request()->query()) }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('ledger.pdf', request()->query()) }}" class="btn btn-outline-danger">Download PDF</a>
        <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Print</button>
    </div>
</div>
@if($account)
    <p class="text-muted no-print mb-3">{{ $account->account_code }} · {{ $account->account_name }}
        @if($from || $to)
            · {{ $from ?: '…' }} to {{ $to ?: '…' }}
        @endif
    </p>
@elseif($from || $to)
    <p class="text-muted no-print mb-3">{{ $from ?: '…' }} to {{ $to ?: '…' }}</p>
@endif
<div class="cb-preview">
    @foreach($pages as $index => $pageRows)
        <div class="mb-4">
            @include('cashbook.partials.sheet', [
                'rows' => $pageRows,
                'title' => $title,
                'pageNo' => str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT),
                'sheetId' => 'p'.$index,
                'company' => $company,
            ])
        </div>
    @endforeach
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[id^="cbWrap-"]').forEach(function (wrap) {
    var sheet = wrap.querySelector('.cb-sheet');
    if (!sheet) return;
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
});
</script>
@endpush
