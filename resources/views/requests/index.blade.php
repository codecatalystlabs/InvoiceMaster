@extends('layouts.app')
@section('title', 'Change requests')
@section('content')
<form class="d-flex gap-2 mb-3">
    <select name="status" class="form-select" style="max-width:200px">
        <option value="">All</option>
        @foreach(['pending','approved','refused'] as $s)
            <option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>
        @endforeach
    </select>
    <button class="btn btn-primary">Filter</button>
</form>
<div class="card"><table class="table mb-0"><thead><tr><th>When</th><th>Who</th><th>Record</th><th>Reason</th><th>Status</th><th></th></tr></thead><tbody>
@forelse($rows as $row)
<tr>
    <td>{{ $row->created_at?->format('d M Y H:i') }}</td>
    <td>{{ $row->requester?->name }}</td>
    <td>{{ $row->entity_type }} #{{ $row->entity_id }}</td>
    <td>{{ $row->reason }}</td>
    <td>{!! status_badge($row->status) !!}</td>
    <td><a href="{{ route('requests.show', $row) }}" class="btn btn-sm btn-outline-secondary">Open</a></td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No change requests.</td></tr>@endforelse
</tbody></table><div class="card-body">{{ $rows->links() }}</div></div>
@endsection
