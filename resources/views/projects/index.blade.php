@extends('layouts.app')
@section('title', 'Projects')
@section('content')
<div class="row g-3">
<div class="col-md-4"><div class="card"><div class="card-header">New project</div><div class="card-body">
<form method="POST" action="{{ route('projects.store') }}">@csrf
    <input name="name" class="form-control mb-2" placeholder="Name" required>
    <select name="client_id" class="form-select mb-2"><option value="">Client (optional)</option>@foreach($clients ?? [] as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
    <input type="number" step="0.01" name="budget" class="form-control mb-2" placeholder="Budget">
    <select name="status" class="form-select mb-2"><option>Active</option><option>On hold</option><option>Closed</option></select>
    <button class="btn btn-primary">Create</button>
</form>
</div></div></div>
<div class="col-md-8"><div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Code</th><th>Name</th><th>Client</th><th>Budget</th><th>Status</th></tr></thead>
<tbody>
@forelse($projects as $p)
<tr>
    <td><a href="{{ route('projects.show', $p) }}">{{ $p->code }}</a></td>
    <td>{{ $p->name }}</td>
    <td>{{ $p->client?->name }}</td>
    <td>{{ money($p->budget) }}</td>
    <td>{!! status_badge($p->status) !!}</td>
</tr>
@empty<tr><td colspan="5">No projects yet.</td></tr>@endforelse
</tbody></table></div></div></div>
</div>
@endsection
