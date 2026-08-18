@extends('layouts.app')
@section('title', $asset->asset_name)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <a href="{{ route('assets.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2">
        <a href="{{ route('assets.edit',$asset) }}" class="btn btn-primary">Edit</a>
        <form method="POST" action="{{ route('assets.destroy', $asset) }}" data-confirm="Delete asset {{ $asset->asset_number }}? This cannot be undone.">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<div class="row g-3">
<div class="col-md-7"><div class="card"><div class="card-body">
    <p>{{ $asset->asset_number }} · {{ $asset->category }} · {{ $asset->condition_status }}</p>
    <p>Purchase {{ money($asset->purchase_price) }} · Current {{ money($asset->current_value) }}</p>
    <p>{{ $asset->location }} {{ $asset->serial_number }}</p>
</div></div></div>
<div class="col-md-5"><div class="card"><div class="card-header">New valuation</div><div class="card-body">
<form method="POST" action="{{ route('assets.value',$asset) }}">@csrf
    <input type="date" name="valuation_date" class="form-control mb-2" value="{{ date('Y-m-d') }}">
    <input type="number" step="0.01" name="valuation_amount" class="form-control mb-2" placeholder="Amount" required>
    <input name="valuation_reason" class="form-control mb-2" placeholder="Reason">
    <button class="btn btn-primary">Record</button>
</form>
<ul class="mt-3 list-unstyled">@foreach($asset->valuations as $v)<li>{{ $v->valuation_date?->format('d M Y') }} — {{ money($v->valuation_amount) }}</li>@endforeach</ul>
</div></div></div>
</div>
@endsection
