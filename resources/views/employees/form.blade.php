@extends('layouts.app')
@section('title', $employee->exists ? 'Edit employee' : 'New employee')
@section('content')
<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" class="card card-body" style="max-width:720px;">
@csrf @if($employee->exists) @method('PUT') @endif
<div class="row g-3">
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $employee->name) }}" required></div>
    <div class="col-md-6"><label class="form-label">Linked user</label>
        <select name="user_id" class="form-select"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('user_id', $employee->user_id)==$u->id)>{{ $u->name }}</option>@endforeach</select>
    </div>
    <div class="col-md-6"><label class="form-label">Department</label>
        <select name="department_id" class="form-select"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id)==$d->id)>{{ $d->name }}</option>@endforeach</select>
    </div>
    <div class="col-md-6"><label class="form-label">Job title</label><input name="job_title" class="form-control" value="{{ old('job_title', $employee->job_title) }}"></div>
    <div class="col-md-6"><label class="form-label">Email</label><input name="email" class="form-control" value="{{ old('email', $employee->email) }}"></div>
    <div class="col-md-6"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}"></div>
    <div class="col-md-6"><label class="form-label">TIN</label><input name="tin" class="form-control" value="{{ old('tin', $employee->tin) }}"></div>
    <div class="col-md-6"><label class="form-label">NSSF number</label><input name="nssf_number" class="form-control" value="{{ old('nssf_number', $employee->nssf_number) }}"></div>
    <div class="col-md-4"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($employee->start_date)->toDateString()) }}"></div>
    <div class="col-md-4"><label class="form-label">Basic salary</label><input type="number" step="0.01" name="basic_salary" class="form-control" value="{{ old('basic_salary', $employee->basic_salary) }}" required></div>
    <div class="col-md-4"><label class="form-label">Allowances</label><input type="number" step="0.01" name="allowances" class="form-control" value="{{ old('allowances', $employee->allowances) }}"></div>
    <div class="col-md-4"><label class="form-label">Pay method</label>
        <select name="pay_method" class="form-select">@foreach(['bank'=>'Bank','mtn_momo'=>'MTN MoMo','airtel_money'=>'Airtel Money','cash'=>'Cash'] as $k=>$v)<option value="{{ $k }}" @selected(old('pay_method', $employee->pay_method)===$k)>{{ $v }}</option>@endforeach</select>
    </div>
    <div class="col-md-4"><label class="form-label">Account / MoMo number</label><input name="pay_account" class="form-control" value="{{ old('pay_account', $employee->pay_account) }}"></div>
    <div class="col-md-4"><label class="form-label">Status</label>
        <select name="status" class="form-select"><option @selected($employee->status==='Active')>Active</option><option @selected($employee->status==='Inactive')>Inactive</option></select>
    </div>
</div>
<button class="btn btn-primary mt-3">Save</button>
</form>
@endsection
