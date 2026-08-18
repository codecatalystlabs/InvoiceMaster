@extends('layouts.app')
@section('title', $employee->exists ? 'Edit employee' : 'New employee')
@section('content')
<form method="POST" action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}" class="card card-body" style="max-width:920px;">
@csrf @if($employee->exists) @method('PUT') @endif
<h2 class="h5">Person</h2>
<div class="row g-3 mb-3">
    <div class="col-md-6"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $employee->name) }}" required></div>
    <div class="col-md-3"><label class="form-label">Gender</label>
        <select name="gender" class="form-select"><option value="">—</option>@foreach(['Male','Female'] as $g)<option value="{{ $g }}" @selected(old('gender', $employee->gender)===$g)>{{ $g }}</option>@endforeach</select>
    </div>
    <div class="col-md-3"><label class="form-label">Date of birth</label><input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', optional($employee->date_of_birth)->toDateString()) }}"></div>
    <div class="col-md-4"><label class="form-label">National ID</label><input name="national_id" class="form-control" value="{{ old('national_id', $employee->national_id) }}"></div>
    <div class="col-md-4"><label class="form-label">Email</label><input name="email" class="form-control" value="{{ old('email', $employee->email) }}"></div>
    <div class="col-md-4"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone', $employee->phone) }}"></div>
    <div class="col-md-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address', $employee->address) }}"></div>
    <div class="col-md-6"><label class="form-label">Next of kin</label><input name="next_of_kin" class="form-control" value="{{ old('next_of_kin', $employee->next_of_kin) }}"></div>
    <div class="col-md-6"><label class="form-label">Next of kin phone</label><input name="next_of_kin_phone" class="form-control" value="{{ old('next_of_kin_phone', $employee->next_of_kin_phone) }}"></div>
</div>
<h2 class="h5">Job</h2>
<div class="row g-3 mb-3">
    <div class="col-md-4"><label class="form-label">Linked user</label>
        <select name="user_id" class="form-select"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(old('user_id', $employee->user_id)==$u->id)>{{ $u->name }}</option>@endforeach</select>
    </div>
    <div class="col-md-4"><label class="form-label">Department</label>
        <select name="department_id" id="department_id" class="form-select">
            <option value="">—</option>
            @foreach($departments as $d)<option value="{{ $d->id }}" @selected(old('department_id', $employee->department_id)==$d->id)>{{ $d->name }}</option>@endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Division</label>
        <select name="division_id" id="division_id" class="form-select">
            <option value="">—</option>
            @foreach($divisions as $div)
            <option value="{{ $div->id }}" data-department="{{ $div->department_id }}" @selected(old('division_id', $employee->division_id)==$div->id)>{{ $div->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Position</label>
        <select name="position_id" id="position_id" class="form-select">
            <option value="">—</option>
            @foreach($positions as $p)
            <option value="{{ $p->id }}" data-department="{{ $p->department_id }}" data-division="{{ $p->division_id }}" data-title="{{ $p->name }}" @selected(old('position_id', $employee->position_id)==$p->id)>{{ $p->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4"><label class="form-label">Job title on payslip</label><input name="job_title" id="job_title" class="form-control" value="{{ old('job_title', $employee->job_title) }}"></div>
    <div class="col-md-4"><label class="form-label">Supervisor</label>
        <select name="supervisor_id" class="form-select"><option value="">—</option>@foreach($supervisors as $s)<option value="{{ $s->id }}" @selected(old('supervisor_id', $employee->supervisor_id)==$s->id)>{{ $s->name }}</option>@endforeach</select>
    </div>
    <div class="col-md-4"><label class="form-label">Employment type</label>
        <select name="employment_type" class="form-select">@foreach(['permanent'=>'Permanent','contract'=>'Contract','intern'=>'Intern','casual'=>'Casual'] as $k=>$v)<option value="{{ $k }}" @selected(old('employment_type', $employee->employment_type ?: 'permanent')===$k)>{{ $v }}</option>@endforeach</select>
    </div>
    <div class="col-md-2"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', optional($employee->start_date)->toDateString()) }}"></div>
    <div class="col-md-2"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', optional($employee->end_date)->toDateString()) }}"></div>
    <div class="col-md-4"><label class="form-label">TIN</label><input name="tin" class="form-control" value="{{ old('tin', $employee->tin) }}"></div>
    <div class="col-md-4"><label class="form-label">NSSF number</label><input name="nssf_number" class="form-control" value="{{ old('nssf_number', $employee->nssf_number) }}"></div>
    <div class="col-md-4"><label class="form-label">Machine PIN</label><input name="machine_pin" class="form-control" value="{{ old('machine_pin', $employee->machine_pin) }}" placeholder="Must match the clock"></div>
    <div class="col-md-4"><label class="form-label">Status</label>
        <select name="status" class="form-select"><option @selected($employee->status==='Active')>Active</option><option @selected($employee->status==='Inactive')>Inactive</option></select>
    </div>
</div>
<h2 class="h5">Pay</h2>
<div class="row g-3">
    <div class="col-md-3"><label class="form-label">Basic salary</label><input type="number" step="0.01" name="basic_salary" class="form-control" value="{{ old('basic_salary', $employee->basic_salary) }}" required></div>
    <div class="col-md-3"><label class="form-label">Allowances</label><input type="number" step="0.01" name="allowances" class="form-control" value="{{ old('allowances', $employee->allowances ?? 0) }}"></div>
    <div class="col-md-3"><label class="form-label">Pay method</label>
        <select name="pay_method" class="form-select">@foreach(['bank'=>'Bank','mtn_momo'=>'MTN MoMo','airtel_money'=>'Airtel Money','cash'=>'Cash'] as $k=>$v)<option value="{{ $k }}" @selected(old('pay_method', $employee->pay_method)===$k)>{{ $v }}</option>@endforeach</select>
    </div>
    <div class="col-md-3"><label class="form-label">Bank name</label><input name="bank_name" class="form-control" value="{{ old('bank_name', $employee->bank_name) }}"></div>
    <div class="col-md-4"><label class="form-label">Account / MoMo number</label><input name="pay_account" class="form-control" value="{{ old('pay_account', $employee->pay_account) }}"></div>
</div>
<button class="btn btn-primary mt-3">Save</button>
@if($employee->exists)<a class="btn btn-link mt-3" href="{{ route('employees.show', $employee) }}">Cancel</a>@endif
</form>
<script>
(function () {
    const dept = document.getElementById('department_id');
    const division = document.getElementById('division_id');
    const position = document.getElementById('position_id');
    const title = document.getElementById('job_title');
    function filter() {
        const d = dept.value;
        [...division.options].forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = d && opt.dataset.department !== d;
        });
        [...position.options].forEach(function (opt) {
            if (!opt.value) return;
            opt.hidden = d && opt.dataset.department !== d;
        });
        if (division.value && division.selectedOptions[0]?.hidden) division.value = '';
        if (position.value && position.selectedOptions[0]?.hidden) position.value = '';
    }
    dept.addEventListener('change', filter);
    position.addEventListener('change', function () {
        const opt = position.selectedOptions[0];
        if (!opt || !opt.value) return;
        if (opt.dataset.department) dept.value = opt.dataset.department;
        filter();
        if (opt.dataset.division) division.value = opt.dataset.division;
        if (opt.dataset.title) title.value = opt.dataset.title;
    });
    filter();
})();
</script>
@endsection
