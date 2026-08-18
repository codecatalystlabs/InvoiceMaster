@extends('layouts.app')
@section('title', 'Public holidays')
@section('content')
<h2 class="h4 mb-3">Public holidays</h2>
<p class="text-muted">Non-working days skip leave counting and mark attendance as holiday unless someone clocks in (then overtime).</p>
<form method="POST" action="{{ route('holidays.store') }}" class="card card-body mb-3 row g-2">@csrf
    <div class="col-md-5"><input name="name" class="form-control" placeholder="Name" required></div>
    <div class="col-md-5"><input type="date" name="holiday_date" class="form-control" required></div>
    <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
</form>
<div class="card"><table class="table mb-0"><thead><tr><th>Date</th><th>Name</th><th></th></tr></thead><tbody>
@forelse($holidays as $h)
<tr>
    <td>{{ $h->holiday_date->toDateString() }}</td>
    <td>{{ $h->name }}</td>
    <td>
        <form method="POST" action="{{ route('holidays.destroy', $h) }}" data-confirm="Remove holiday {{ $h->name }}?">@csrf @method('DELETE')
            <button class="btn btn-sm btn-outline-danger">Remove</button>
        </form>
    </td>
</tr>
@empty<tr><td colspan="3" class="text-muted">No holidays yet.</td></tr>@endforelse
</tbody></table></div>
@endsection
