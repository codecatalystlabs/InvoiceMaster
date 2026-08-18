@extends('layouts.app')
@section('title', 'Audit log')
@section('content')
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>When</th><th>User</th><th>Action</th><th>Entity</th><th>Details</th></tr></thead><tbody>
@forelse($logs as $log)
<tr><td>{{ $log->created_at }}</td><td>{{ $log->user?->name }}</td><td>{{ $log->action }}</td><td>{{ $log->entity_type }} #{{ $log->entity_id }}</td><td>{{ $log->details }}</td></tr>
@empty<tr><td colspan="5">No logs</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $logs->links() }}</div></div>
@endsection
