@extends('layouts.app')
@section('title', 'New pay run')
@section('content')
<form class="card card-body mb-3 row g-2" method="GET">
    <div class="col-md-3"><input type="number" name="year" class="form-control" value="{{ $year }}"></div>
    <div class="col-md-3"><input type="number" name="month" min="1" max="12" class="form-control" value="{{ $month }}"></div>
    <div class="col-md-2"><button class="btn btn-secondary w-100">Preview</button></div>
</form>
<div class="card mb-3"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Employee</th><th class="text-end">Gross</th><th class="text-end">PAYE</th><th class="text-end">NSSF emp</th><th class="text-end">LST</th><th class="text-end">Canteen</th><th class="text-end">Net</th></tr></thead>
<tbody>
@foreach($preview['lines'] as $line)
<tr>
    <td>{{ collect($preview['employees'])->firstWhere('id', $line['employee_id'])?->name }}</td>
    <td class="text-end">{{ money($line['gross']) }}</td>
    <td class="text-end">{{ money($line['paye']) }}</td>
    <td class="text-end">{{ money($line['nssf_employee']) }}</td>
    <td class="text-end">{{ money($line['lst']) }}</td>
    <td class="text-end">{{ money($line['canteen']) }}</td>
    <td class="text-end">{{ money($line['net']) }}</td>
</tr>
@endforeach
<tr class="table-light"><td><strong>Totals</strong></td>
    <td class="text-end">{{ money($preview['totals']['gross']) }}</td>
    <td class="text-end">{{ money($preview['totals']['paye']) }}</td>
    <td class="text-end">{{ money($preview['totals']['nssf_employee']) }}</td>
    <td class="text-end">{{ money($preview['totals']['lst']) }}</td>
    <td class="text-end">{{ money($preview['totals']['canteen']) }}</td>
    <td class="text-end">{{ money($preview['totals']['net']) }}</td>
</tr>
</tbody></table></div></div>
<form method="POST" action="{{ route('payroll.store') }}">@csrf
    <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
    <button class="btn btn-primary">Save draft</button>
</form>
@endsection
