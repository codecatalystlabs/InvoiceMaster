@extends('layouts.app')
@section('title', 'Export data')
@section('content')
<p class="text-muted">Download CSV datasets. Optional date range applies to dated sets.</p>
<form class="row g-2 mb-4" id="range"><div class="col-md-3"><input type="date" id="from" class="form-control"></div><div class="col-md-3"><input type="date" id="to" class="form-control"></div></form>
<div class="row g-3">
@foreach(['invoices'=>'Invoices','quotations'=>'Quotations','receipts'=>'Receipts','clients'=>'Clients','canteen'=>'Canteen meals','expenses'=>'Expenses','cashbook'=>'Cash book','ledger'=>'Ledger','assets'=>'Assets','services'=>'Services'] as $type=>$label)
@continue(!can_module($type === 'canteen' ? 'canteen' : $type))
<div class="col-md-4"><div class="card h-100"><div class="card-body d-flex flex-column"><h5>{{ $label }}</h5>
<a class="btn btn-primary mt-auto export-link" href="{{ route('exports.download',$type) }}" data-type="{{ $type }}">Download CSV</a></div></div></div>
@endforeach
</div>
<script>
document.querySelectorAll('#from,#to').forEach(el => el.addEventListener('change', () => {
    const from = document.getElementById('from').value, to = document.getElementById('to').value;
    document.querySelectorAll('.export-link').forEach(a => {
        const u = new URL(a.href.split('?')[0], window.location.origin);
        if (from) u.searchParams.set('from', from);
        if (to) u.searchParams.set('to', to);
        a.href = u.pathname + u.search;
    });
}));
</script>
@endsection
