@extends('layouts.app')
@section('title', 'New bill')
@section('content')
<form method="POST" action="{{ route('bills.store') }}">
@csrf
<div class="row g-3">
<div class="col-md-8">
<div class="card mb-3"><div class="card-body row g-3">
    <div class="col-md-6"><label class="form-label">Vendor</label><input name="vendor_name" class="form-control" required></div>
    <div class="col-md-3"><label class="form-label">Date</label><input type="date" name="bill_date" class="form-control" value="{{ date('Y-m-d') }}"></div>
    <div class="col-md-3"><label class="form-label">Due</label><input type="date" name="due_date" class="form-control" value="{{ now()->addDays(14)->toDateString() }}"></div>
    <div class="col-md-6"><label class="form-label">Expense account</label>
        <select name="account_id" class="form-select"><option value="">5200 Software default</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->account_code }} {{ $a->account_name }}</option>@endforeach</select>
    </div>
    <div class="col-md-6"><label class="form-label">Project</label>
        <select name="project_id" class="form-select"><option value="">—</option>@foreach($projects as $p)<option value="{{ $p->id }}">{{ $p->code }} {{ $p->name }}</option>@endforeach</select>
    </div>
    <div class="col-md-4"><label class="form-label">VAT / tax</label><input type="number" step="0.01" name="tax" class="form-control" value="0"></div>
    <div class="col-12"><label class="form-label">Notes</label><textarea name="notes" class="form-control"></textarea></div>
</div></div>
<div class="card"><div class="card-header d-flex justify-content-between">Lines <button type="button" class="btn btn-sm btn-primary" onclick="addItemRow('itemsTable')">Add</button></div>
<div class="card-body"><table class="table" id="itemsTable"><thead><tr><th>Item</th><th>Qty</th><th>Price</th><th></th></tr></thead>
<tbody>
<tr>
    <td><input name="items[0][item_name]" class="form-control" required></td>
    <td><input type="number" step="0.01" name="items[0][qty]" class="form-control qty" value="1"></td>
    <td><input type="number" step="0.01" name="items[0][unit_price]" class="form-control price"></td>
    <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()">×</button></td>
</tr>
</tbody></table></div></div>
</div>
<div class="col-md-4"><div class="card card-body"><button class="btn btn-primary">Save bill</button></div></div>
</div>
</form>
@endsection
