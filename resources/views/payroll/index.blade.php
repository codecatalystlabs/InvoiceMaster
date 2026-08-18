@extends('layouts.app')
@section('title', 'Payroll')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4 mb-0">Payroll</h2>
    @if(!auth()->user()->seesOnlyOwnRecords())
        <a href="{{ route('payroll.create') }}" class="btn btn-primary">New pay run</a>
    @endif
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Period</th><th>Status</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">NSSF</th><th class="text-end">Net</th><th></th></tr></thead>
<tbody>
@forelse($runs as $run)
<tr>
    <td><a href="{{ route('payroll.show', $run) }}">{{ $run->periodLabel() }}</a></td>
    <td>{!! status_badge($run->status) !!}</td>
    <td class="text-end">{{ money($run->gross) }}</td>
    <td class="text-end">{{ money($run->paye) }}</td>
    <td class="text-end">{{ money($run->nssf_employee + $run->nssf_employer) }}</td>
    <td class="text-end">{{ money($run->net) }}</td>
    <td><a href="{{ route('payroll.show', $run) }}">Open</a></td>
</tr>
@empty<tr><td colspan="7">No payroll runs yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $runs->links() }}</div></div>
@endsection
