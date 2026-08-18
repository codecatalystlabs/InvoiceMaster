@extends('layouts.app')
@section('title', $service->exists ? 'Edit service' : 'New service')
@section('content')
<form method="POST" action="{{ $service->exists ? route('services.update',$service) : route('services.store') }}" class="card"><div class="card-body">
@csrf @if($service->exists) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label><input name="service_name" class="form-control" value="{{ $service->service_name }}" required></div>
    <div class="col-md-6"><label class="form-label">Provider</label><input name="provider_name" class="form-control" value="{{ $service->provider_name }}" required></div>
    <div class="col-md-4"><label class="form-label">Cost</label><input type="number" step="0.01" name="cost" class="form-control" value="{{ $service->cost }}" required></div>
    <div class="col-md-4"><label class="form-label">Frequency</label><select name="billing_frequency" class="form-select">@foreach(['Monthly','Quarterly','Yearly','Weekly','Daily'] as $f)<option @selected($service->billing_frequency==$f)>{{ $f }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Status</label><select name="status" class="form-select">@foreach(['Active','Suspended','Cancelled','Expired'] as $s)<option @selected($service->status==$s)>{{ $s }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Start</label><input type="date" name="start_date" class="form-control" value="{{ optional($service->start_date)->toDateString() ?? date('Y-m-d') }}"></div>
    <div class="col-md-4"><label class="form-label">Next billing</label><input type="date" name="next_billing_date" class="form-control" value="{{ optional($service->next_billing_date)->toDateString() ?? date('Y-m-d') }}"></div>
    <div class="col-md-4"><label class="form-label">End</label><input type="date" name="end_date" class="form-control" value="{{ optional($service->end_date)->toDateString() }}"></div>
    <div class="col-md-6"><label class="form-label">Contact</label><input name="provider_contact" class="form-control" value="{{ $service->provider_contact }}"></div>
    <div class="col-md-6"><label class="form-label">Category</label><input name="category" class="form-control" value="{{ $service->category }}"></div>
    <div class="col-12 form-check"><input type="checkbox" class="form-check-input" name="auto_renew" value="1" @checked($service->auto_renew ?? true)><label class="form-check-label">Auto renew</label></div>
    <div class="col-12"><textarea name="description" class="form-control">{{ $service->description }}</textarea></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
</div></form>
@endsection
