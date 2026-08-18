@extends('layouts.app')
@section('title', $department->name)
@section('content')
<div class="d-flex justify-content-between mb-3">
    <div>
        <a href="{{ route('departments.index') }}">Organisation</a>
        <h2 class="h4 mb-0">{{ $department->code }} · {{ $department->name }}</h2>
        <div class="text-muted">Head {{ $department->head?->name ?? '—' }} · {{ $department->employees_count }} staff</div>
    </div>
    <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#edDept">Edit department</button>
</div>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header">Divisions</div>
            <form method="POST" action="{{ route('departments.divisions.store', $department) }}" class="card-body row g-2 border-bottom">@csrf
                <div class="col-7"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-3"><input name="code" class="form-control" placeholder="Code" required></div>
                <div class="col-2"><button class="btn btn-primary w-100">Add</button></div>
            </form>
            <table class="table mb-0">
                <thead><tr><th>Name</th><th>Code</th><th>Positions</th></tr></thead>
                <tbody>
                @forelse($department->divisions as $div)
                <tr>
                    <td>{{ $div->name }}</td>
                    <td>{{ $div->code }}</td>
                    <td>{{ $department->positions->where('division_id', $div->id)->count() }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-muted">No divisions yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header">Positions</div>
            <form method="POST" action="{{ route('departments.positions.store', $department) }}" class="card-body row g-2 border-bottom">@csrf
                <div class="col-md-4"><input name="name" class="form-control" placeholder="Name" required></div>
                <div class="col-md-2"><input name="code" class="form-control" placeholder="Code" required></div>
                <div class="col-md-3">
                    <select name="division_id" class="form-select">
                        <option value="">Division</option>
                        @foreach($department->divisions as $div)<option value="{{ $div->id }}">{{ $div->name }}</option>@endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="level" class="form-select">
                        @foreach(['intern'=>'Intern','junior'=>'Junior','mid'=>'Mid','senior'=>'Senior','lead'=>'Lead','manager'=>'Manager','director'=>'Director','executive'=>'Executive'] as $k=>$v)
                        <option value="{{ $k }}" @selected($k==='mid')>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
            </form>
            <div class="table-responsive"><table class="table mb-0">
                <thead><tr><th>Position</th><th>Division</th><th>Level</th></tr></thead>
                <tbody>
                @forelse($department->positions as $p)
                <tr>
                    <td>{{ $p->name }}</td>
                    <td>{{ $p->division?->name ?? '—' }}</td>
                    <td>{{ $p->levelLabel() }}</td>
                </tr>
                @empty
                <tr><td colspan="3" class="text-muted">No positions yet.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </div>
    </div>
</div>
<div class="modal fade" id="edDept"><div class="modal-dialog"><form method="POST" action="{{ route('departments.update', $department) }}" class="modal-content">@csrf @method('PUT')
<div class="modal-header"><h5>Edit {{ $department->name }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
    <label class="form-label">Name</label>
    <input name="name" class="form-control mb-2" value="{{ $department->name }}" required>
    <label class="form-label">Code</label>
    <input name="code" class="form-control mb-2" value="{{ $department->code }}">
    <label class="form-label">Head</label>
    <select name="head_user_id" class="form-select mb-2"><option value="">None</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected($department->head_user_id==$u->id)>{{ $u->name }}</option>@endforeach</select>
    <label class="form-label">Status</label>
    <select name="is_active" class="form-select"><option value="1" @selected($department->is_active)>Active</option><option value="0" @selected(!$department->is_active)>Inactive</option></select>
</div>
<div class="modal-footer"><button class="btn btn-primary">Save</button></div>
</form></div></div>
@endsection
