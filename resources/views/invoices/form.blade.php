@extends('layouts.app')
@section('title', $invoice->exists ? 'Edit invoice' : 'New invoice')
@section('content')
<form method="POST" action="{{ $invoice->exists ? route('invoices.update', $invoice) : route('invoices.store') }}">
@csrf @if($invoice->exists) @method('PUT') @endif
<div class="row g-3">
<div class="col-md-8">
<div class="card mb-3"><div class="card-body">
    <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Client</label>
            <select name="client_id" class="form-select">
                <option value="">Walk-in / named below</option>
                @foreach($clients as $c)<option value="{{ $c->id }}" @selected(old('client_id', $invoice->client_id)==$c->id)>{{ $c->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Client name (optional)</label><input name="client_name" class="form-control" value="{{ old('client_name', $invoice->client_name) }}"></div>
        <div class="col-md-4"><label class="form-label">Date</label><input type="date" name="date" class="form-control" value="{{ old('date', optional($invoice->date)->toDateString() ?? date('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label">Due date</label><input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($invoice->due_date)->toDateString()) }}"></div>
        <div class="col-md-4"><label class="form-label">Status</label>
            <select name="status" class="form-select">
                @foreach(['draft','proforma','sent','Unpaid','Partially Paid','Paid','Overdue','Cancelled'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $invoice->status)==$s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control">{{ old('notes', $invoice->notes) }}</textarea></div>
    </div>
</div></div>
<div class="card"><div class="card-header d-flex justify-content-between">Items <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow('itemsTable')">Add</button></div>
<div class="card-body"><table class="table" id="itemsTable"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Total</th><th></th></tr></thead><tbody>
@php $items = old('items', $invoice->items?->toArray() ?? [['item_name'=>'','qty'=>1,'unit_price'=>0]]); @endphp
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
<div class="col-md-4">
<div class="card"><div class="card-body">
    <label class="form-label">Tax %</label><input type="number" step="0.01" id="tax_rate" name="tax_rate" class="form-control mb-2" value="{{ old('tax_rate', auth()->user()->company->tax_rate) }}">
    <label class="form-label">Discount</label><input type="number" step="0.01" id="discount" name="discount" class="form-control mb-3" value="{{ old('discount', $invoice->discount) }}">
    <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="sumSub">{{ number_format($invoice->subtotal ?? 0) }}</strong></div>
    <div class="d-flex justify-content-between"><span>Tax</span><strong id="sumTax">{{ number_format($invoice->tax ?? 0) }}</strong></div>
    <div class="d-flex justify-content-between fs-5"><span>Total</span><strong id="sumTotal">{{ number_format($invoice->total ?? 0) }}</strong></div>
    <button class="btn btn-primary w-100 mt-3">Save invoice</button>
</div></div>
</div>
</div>
</form>
@endsection
