@extends('layouts.app')
@section('title', $asset->exists ? 'Edit asset' : 'New asset')
@section('content')
<form method="POST" action="{{ $asset->exists ? route('assets.update',$asset) : route('assets.store') }}" class="card"><div class="card-body">
@csrf @if($asset->exists) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label><input name="asset_name" class="form-control" value="{{ old('asset_name',$asset->asset_name) }}" required></div>
    <div class="col-md-6"><label class="form-label">Category</label><input name="category" class="form-control" value="{{ old('category',$asset->category) }}" required></div>
    <div class="col-md-4"><label class="form-label">Purchase date</label><input type="date" name="purchase_date" class="form-control" value="{{ old('purchase_date', optional($asset->purchase_date)->toDateString() ?? date('Y-m-d')) }}"></div>
    <div class="col-md-4"><label class="form-label">Purchase price</label><input type="number" step="0.01" name="purchase_price" class="form-control" value="{{ old('purchase_price',$asset->purchase_price) }}" required></div>
    <div class="col-md-4"><label class="form-label">Current value</label><input type="number" step="0.01" name="current_value" class="form-control" value="{{ old('current_value',$asset->current_value) }}"></div>
    <div class="col-md-4"><label class="form-label">Depreciation %</label><input type="number" step="0.01" name="depreciation_rate" class="form-control" value="{{ old('depreciation_rate',$asset->depreciation_rate) }}"></div>
    <div class="col-md-4"><label class="form-label">Method</label><select name="depreciation_method" class="form-select">@foreach(['None','Straight Line','Declining Balance'] as $m)<option @selected($asset->depreciation_method==$m)>{{ $m }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label">Condition</label><select name="condition_status" class="form-select">@foreach(['Excellent','Good','Fair','Poor','Damaged'] as $c)<option @selected($asset->condition_status==$c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label">Location</label><input name="location" class="form-control" value="{{ $asset->location }}"></div>
    <div class="col-md-6"><label class="form-label">Serial</label><input name="serial_number" class="form-control" value="{{ $asset->serial_number }}"></div>
    <div class="col-md-6"><label class="form-label">Warranty expiry</label><input type="date" name="warranty_expiry" class="form-control" value="{{ optional($asset->warranty_expiry)->toDateString() }}"></div>
    <div class="col-12"><textarea name="description" class="form-control" placeholder="Description">{{ $asset->description }}</textarea></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
</div></form>
@endsection
