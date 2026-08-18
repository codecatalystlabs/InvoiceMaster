@extends('layouts.app')
@section('title', 'Financial report')
@section('content')
<form class="card card-body mb-3 row g-2" method="GET">
    <div class="col-md-4"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-md-4"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Generate</button></div>
</form>
<div class="row g-3">
<div class="col-md-6"><div class="card"><div class="card-header">Income statement</div><div class="card-body">
    <div class="d-flex justify-content-between"><span>Revenue</span><strong>{{ money($revenue) }}</strong></div>
    <hr>
    @foreach($expenseRows as $row)<div class="d-flex justify-content-between"><span>{{ $row->category }}</span><span>{{ money($row->total) }}</span></div>@endforeach
    <div class="d-flex justify-content-between mt-2"><strong>Total expenses</strong><strong>{{ money($totalExpenses) }}</strong></div>
    <hr>
    <div class="d-flex justify-content-between fs-5"><span>Net</span><strong>{{ money($net) }}</strong></div>
</div></div></div>
<div class="col-md-6"><div class="card"><div class="card-header">Balance sheet as of {{ $to }}</div><div class="card-body">
    <div class="d-flex justify-content-between"><span>Cash</span><span>{{ money($cash) }}</span></div>
    <div class="d-flex justify-content-between"><span>Receivables</span><span>{{ money($receivable) }}</span></div>
    <div class="d-flex justify-content-between"><span>Fixed assets</span><span>{{ money($assets) }}</span></div>
    <div class="d-flex justify-content-between"><strong>Total assets</strong><strong>{{ money($totalAssets) }}</strong></div>
    <hr>
    <div class="d-flex justify-content-between"><span>Payables</span><span>{{ money($payable) }}</span></div>
    <div class="d-flex justify-content-between"><span>Equity</span><span>{{ money($equity) }}</span></div>
</div></div></div>
</div>
@endsection
