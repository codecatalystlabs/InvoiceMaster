@extends('layouts.app')
@section('title', 'Employees')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <h2 class="h4 mb-0">Employees</h2>
    <a href="{{ route('employees.create') }}" class="btn btn-primary">Add employee</a>
</div>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>No.</th><th>Name</th><th>Job</th><th>PIN</th><th>Basic</th><th>Pay</th><th>Status</th><th></th></tr></thead>
<tbody>
@forelse($employees as $e)
<tr>
    <td>{{ $e->number }}</td>
    <td>{{ $e->name }}</td>
    <td>{{ $e->position?->name ?? $e->job_title }}{{ $e->division ? ' · '.$e->division->name : ($e->department ? ' · '.$e->department->name : '') }}</td>
    <td>{{ $e->machine_pin ?: $e->pin() }}</td>
    <td>{{ money($e->basic_salary) }}</td>
    <td>{{ $e->pay_method }} {{ $e->pay_account }}</td>
    <td>{!! status_badge($e->status) !!}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('employees.show', $e),
            'edit' => route('employees.edit', $e),
            'delete' => route('employees.destroy', $e),
            'confirm' => 'Delete employee '.$e->name.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="8">No employees yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $employees->links() }}</div></div>
@endsection
