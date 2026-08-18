@extends('layouts.app')
@section('title', 'New petty cash fund')
@section('content')
<form method="POST" action="{{ route('petty-cash.store') }}" class="card card-body">@csrf
    <div class="mb-2"><label class="form-label">Name</label><input name="name" class="form-control" value="Main petty cash" required></div>
    <div class="mb-2"><label class="form-label">Department</label>
        <select name="department_id" class="form-select"><option value="">Company-wide</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select>
    </div>
    <div class="mb-2"><label class="form-label">Custodian</label>
        <select name="custodian_user_id" class="form-select"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
    </div>
            <div class="mb-2"><label class="form-label">Float limit</label><input type="number" step="0.01" name="float_limit" class="form-control" value="0" required>
            <div class="form-text">Maximum cash this tin may hold. Use 0 for no cap. Top-ups cannot exceed this.</div></div>
    <button class="btn btn-primary">Create fund</button>
</form>
@endsection
