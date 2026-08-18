@extends('layouts.app')
@section('title', 'New requisition')
@section('content')
<form method="POST" action="{{ route('requisitions.store') }}" class="card card-body">@csrf
    <div class="mb-2"><label class="form-label">Title</label><input name="title" class="form-control" required></div>
    <div class="mb-2"><label class="form-label">Purpose</label><textarea name="purpose" class="form-control"></textarea></div>
    <div class="row g-2">
        <div class="col-md-4"><label class="form-label">Amount</label><input type="number" step="0.01" name="amount" class="form-control" required></div>
        <div class="col-md-4"><label class="form-label">Type</label>
            <select name="type" class="form-select"><option value="petty_cash">Petty cash</option><option value="general">General</option></select>
        </div>
        <div class="col-md-4"><label class="form-label">Department</label>
            <select name="department_id" class="form-select">
                <option value="">{{ auth()->user()->department?->name ?? 'None' }}</option>
                @foreach($departments as $d)<option value="{{ $d->id }}" @selected(auth()->user()->department_id==$d->id)>{{ $d->name }}</option>@endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Budget allocation</label>
            <select name="budget_allocation_id" class="form-select">
                <option value="">Optional</option>
                @foreach($allocations as $a)<option value="{{ $a->id }}">{{ $a->budget?->department?->name }} · {{ $a->name }} ({{ money($a->available()) }} left)</option>@endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Preferred petty cash fund</label>
            <select name="petty_cash_fund_id" class="form-select">
                <option value="">Decide at disbursement</option>
                @foreach($funds as $f)<option value="{{ $f->id }}">{{ $f->name }} · {{ money($f->balance) }}</option>@endforeach
            </select>
        </div>
    </div>
    <button class="btn btn-primary mt-3">Submit request</button>
</form>
@endsection
