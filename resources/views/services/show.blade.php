@extends('layouts.app')
@section('title', $service->service_name)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('services.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2">
        <a href="{{ route('services.edit',$service) }}" class="btn btn-primary">Edit</a>
        <form method="POST" action="{{ route('services.destroy', $service) }}" onsubmit="return confirm('Delete this service?')">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<div class="row g-3">
<div class="col-md-7"><div class="card"><div class="card-body">
    <p>{{ $service->provider_name }} · {{ money($service->cost) }} / {{ $service->billing_frequency }}</p>
    <p>Next bill {{ $service->next_billing_date?->format('d M Y') }} · {!! status_badge($service->status) !!}</p>
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header">Record payment</div><div class="card-body">
<form method="POST" action="{{ route('services.pay',$service) }}">@csrf
    <input type="date" name="payment_date" class="form-control mb-2" value="{{ date('Y-m-d') }}">
    <input type="number" step="0.01" name="amount" class="form-control mb-2" value="{{ $service->cost }}" required>
    <select name="payment_method" class="form-select mb-2">@foreach(['Cash','Bank Transfer','Mobile Money','Card'] as $m)<option>{{ $m }}</option>@endforeach</select>
    <input name="reference_number" class="form-control mb-2" placeholder="Reference">
    <button class="btn btn-primary">Save payment</button>
</form>
<ul class="mt-3 list-unstyled">@foreach($service->payments as $p)<li>{{ $p->payment_date?->format('d M Y') }} — {{ money($p->amount) }}</li>@endforeach</ul>
</div></div></div>
</div>
@endsection
