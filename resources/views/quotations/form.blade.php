@extends('layouts.app')
@section('title', $quotation->id ? 'Edit quotation' : 'New quotation')
@section('content')
<form method="POST" action="{{ $quotation->id ? route('quotations.update', $quotation) : route('quotations.store') }}">
@csrf @if($quotation->id) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-8">
<div class="card mb-3"><div class="card-body row g-3">
    <div class="col-md-6"><label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
            <option value="">Select</option>
            @foreach($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id',$quotation->client_id)==$c->id)>{{ $c->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ old('date', optional($quotation->date)->toDateString() ?? date('Y-m-d')) }}"></div>
    <div class="col-md-3"><label class="form-label">Status</label>
        <select name="status" class="form-select">@foreach(['Draft','Sent','Accepted','Rejected'] as $s)<option value="{{ $s }}" @selected(old('status',$quotation->status??'Draft')==$s)>{{ $s }}</option>@endforeach</select>
    </div>
    <div class="col-12"><textarea name="notes" class="form-control" placeholder="Notes">{{ old('notes',$quotation->notes) }}</textarea></div>
</div></div>
<div class="card"><div class="card-header d-flex justify-content-between">Items <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow('itemsTable')">Add</button></div>
<div class="card-body"><table class="table" id="itemsTable"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead><tbody>
@php $items = old('items', $quotation->items?->toArray() ?? [['item_name'=>'','qty'=>1,'unit_price'=>0]]); @endphp
@foreach($items as $i => $item)
<tr>
    <td><input name="items[{{ $i }}][item_name]" class="form-control" value="{{ $item['item_name'] ?? '' }}" required></td>
    <td><input type="number" step="0.01" name="items[{{ $i }}][qty]" class="form-control qty" value="{{ $item['qty'] ?? 1 }}"></td>
    <td><input type="number" step="0.01" name="items[{{ $i }}][unit_price]" class="form-control price" value="{{ $item['unit_price'] ?? 0 }}"></td>
    <td class="line-total">{{ $item['total'] ?? 0 }}</td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove();calcTotals()">×</button></td>
</tr>
@endforeach
</tbody></table></div></div>
</div>
<div class="col-md-4"><div class="card"><div class="card-body">
    <label class="form-label">Tax %</label><input type="number" step="0.01" id="tax_rate" name="tax_rate" class="form-control mb-2" value="{{ old('tax_rate', auth()->user()->company->tax_rate) }}">
    <label class="form-label">Discount</label><input type="number" step="0.01" id="discount" name="discount" class="form-control mb-3" value="{{ old('discount', $quotation->discount) }}">
    <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="sumSub">0</strong></div>
    <div class="d-flex justify-content-between"><span>Tax</span><strong id="sumTax">0</strong></div>
    <div class="d-flex justify-content-between fs-5"><span>Total</span><strong id="sumTotal">0</strong></div>
    <button class="btn btn-primary w-100 mt-3">Save</button>
</div></div></div>
</div>
</form>
@endsection
