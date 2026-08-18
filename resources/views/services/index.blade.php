@extends('layouts.app')
@section('title', 'Services')
@section('content')
<div class="d-flex justify-content-between mb-3"><h5>Est. monthly {{ money($monthly) }}</h5><div class="d-flex gap-2"><a href="{{ route('exports.download','services') }}" class="btn btn-outline-success">CSV</a><a href="{{ route('services.create') }}" class="btn btn-primary">Add service</a></div></div>
<div class="card"><div class="table-responsive"><table class="table mb-0"><thead><tr><th>#</th><th>Name</th><th>Provider</th><th>Cost</th><th>Next bill</th><th>Status</th><th>Actions</th></tr></thead><tbody>
@forelse($services as $s)
<tr>
    <td><a href="{{ route('services.show',$s) }}">{{ $s->service_number }}</a></td>
    <td>{{ $s->service_name }}</td>
    <td>{{ $s->provider_name }}</td>
    <td>{{ money($s->cost) }} / {{ $s->billing_frequency }}</td>
    <td>{{ $s->next_billing_date?->format('d M Y') }}</td>
    <td>{!! status_badge($s->status) !!}</td>
    <td>
        @include('partials.row-actions', [
            'view' => route('services.show', $s),
            'edit' => route('services.edit', $s),
            'delete' => route('services.destroy', $s),
            'confirm' => 'Delete service '.$s->service_number.'?',
        ])
    </td>
</tr>
@empty<tr><td colspan="7">None</td></tr>@endforelse
</tbody></table></div><div class="card-body">{{ $services->links() }}</div></div>
@endsection
