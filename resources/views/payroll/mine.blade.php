@extends('layouts.app')
@section('title', 'My payslips')
@section('content')
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Period</th><th class="text-end">Gross</th><th class="text-end">Net</th><th></th></tr></thead>
<tbody>
@forelse($items as $item)
<tr>
    <td>{{ $item->run?->periodLabel() }}</td>
    <td class="text-end">{{ money($item->gross) }}</td>
    <td class="text-end">{{ money($item->net) }}</td>
    <td><a href="{{ route('payroll.payslip', $item) }}">Download</a></td>
</tr>
@empty<tr><td colspan="4">No payslips yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $items->links() }}</div></div>
@endsection
