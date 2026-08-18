@extends('layouts.app')
@section('title', $project->name)
@section('content')
<a href="{{ route('projects.index') }}" class="btn btn-secondary mb-3">Back</a>
<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card"><h6>Budget</h6><h3>{{ money($project->budget) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Invoiced</h6><h3>{{ money($invoiced) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Costs</h6><h3>{{ money($costs) }}</h3></div></div>
    <div class="col-md-3"><div class="stat-card"><h6>Margin</h6><h3>{{ money($invoiced - $costs) }}</h3></div></div>
</div>
<p class="text-muted">{{ $project->client?->name }} · {!! status_badge($project->status) !!}</p>
<p>Attach invoices, expenses, and bills to this project using the project field on those forms. Recurring client invoices can also be tagged when you add that field later.</p>
@endsection
