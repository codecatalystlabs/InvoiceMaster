@extends('layouts.app')
@section('title', 'Payroll '.$run->periodLabel())
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('payroll.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2">
        <a href="{{ route('payroll.bulk', $run) }}" class="btn btn-outline-primary">MoMo/bank payout CSV</a>
        @if($run->status !== 'posted')
            <form method="POST" action="{{ route('payroll.post', $run) }}">@csrf<button class="btn btn-success">Post to ledger</button></form>
        @endif
    </div>
</div>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><h6>Gross</h6><h3>{{ money($run->gross) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>PAYE</h6><h3>{{ money($run->paye) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Canteen recovered</h6><h3>{{ money($run->canteen) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Net pay</h6><h3>{{ money($run->net) }}</h3></div></div>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Employee</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">NSSF</th><th class="text-end">LST</th><th class="text-end">Canteen</th><th class="text-end">Net</th><th></th></tr></thead>
<tbody>
@foreach($run->items as $item)
<tr>
    <td>{{ $item->employee?->name }}</td>
    <td class="text-end">{{ money($item->gross) }}</td>
    <td class="text-end">{{ money($item->paye) }}</td>
    <td class="text-end">{{ money($item->nssf_employee) }}</td>
    <td class="text-end">{{ money($item->lst) }}</td>
    <td class="text-end">{{ money($item->canteen) }}</td>
    <td class="text-end">{{ money($item->net) }}</td>
    <td><a href="{{ route('payroll.payslip', $item) }}">Payslip</a></td>
</tr>
@endforeach
</tbody></table></div></div>
@endsection
