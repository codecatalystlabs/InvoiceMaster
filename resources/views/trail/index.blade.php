@extends('layouts.app')
@section('title', 'Transaction trail')
@section('content')
<form class="d-flex gap-2 mb-3 flex-wrap">
    <input name="q" value="{{ request('q') }}" class="form-control" placeholder="Search action, module, details" style="max-width:280px">
    <select name="module" class="form-select" style="max-width:200px">
        <option value="">All modules</option>
        @foreach($modules as $module)
            <option value="{{ $module }}" @selected(request('module')===$module)>{{ $module }}</option>
        @endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="form-control" style="max-width:170px">
    <input type="date" name="to" value="{{ request('to') }}" class="form-control" style="max-width:170px">
    <button class="btn btn-primary">Filter</button>
</form>
<div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>When</th><th>User</th><th>Module</th><th>Action</th><th>Record</th><th>Amount</th><th>Details</th></tr></thead>
<tbody>
@forelse($events as $event)
<tr>
    <td>{{ $event->occurred_at?->format('d M Y H:i') }}</td>
    <td>{{ $event->user?->name }}</td>
    <td>{{ $event->module }}</td>
    <td>{{ $event->event_type }}</td>
    <td>{{ $event->entity_type }} @if($event->entity_id)#{{ $event->entity_id }}@endif</td>
    <td>{{ $event->amount !== null ? money($event->amount) : '—' }}</td>
    <td>{{ $event->description }}</td>
</tr>
@empty<tr><td colspan="7" class="text-muted">No trail yet.</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $events->links() }}</div></div>
@endsection
