@extends('layouts.app')
@section('title', 'Bank accounts')
@section('content')
<div class="row g-3">
<div class="col-md-5"><div class="card"><div class="card-header">Add account</div><div class="card-body">
<form method="POST" action="{{ route('banks.store') }}">@csrf
    <input name="name" class="form-control mb-2" placeholder="Name (e.g. Stanbic operations)" required>
    <input name="bank_name" class="form-control mb-2" placeholder="Bank">
    <input name="account_number" class="form-control mb-2" placeholder="Account number">
    <div class="form-check mb-2"><input class="form-check-input" type="checkbox" name="is_default" value="1" id="def"><label class="form-check-label" for="def">Default</label></div>
    <button class="btn btn-primary">Save</button>
</form>
</div></div>
<div class="col-md-7"><div class="card"><div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Name</th><th>Bank</th><th>Number</th><th></th></tr></thead>
<tbody>
@forelse($accounts as $a)
<tr>
    <td>{{ $a->name }} @if($a->is_default)<span class="badge bg-secondary">Default</span>@endif</td>
    <td>{{ $a->bank_name }}</td>
    <td>{{ $a->account_number }}</td>
    <td><a href="{{ route('banks.statements', $a) }}">Upload statement</a></td>
</tr>
@empty<tr><td colspan="4">No named bank accounts yet. Cash still posts to 1110/1120.</td></tr>@endforelse
</tbody></table></div></div></div>
</div>
@endsection
