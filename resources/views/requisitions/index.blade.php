@extends('layouts.app')
@section('title', 'Requisitions')
@section('content')
<div class="d-flex justify-content-between mb-3">
    <form class="d-flex gap-2">
        <select name="status" class="form-select">
            <option value="">All statuses</option>
            @foreach(\App\Models\Requisition::statuses() as $k=>$v)
                <option value="{{ $k }}" @selected(request('status')===$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn btn-primary">Filter</button>
    </form>
    <a href="{{ route('requisitions.create') }}" class="btn btn-primary">New request</a>
</div>
<p class="text-muted">Request → approve → issue from the tin → account → accept. Spend posts as an expense. Leftover cash returns to the tin.</p>
<div class="card"><table class="table mb-0"><thead><tr><th>#</th><th>Title</th><th>Who</th><th>Dept</th><th>Amount</th><th>Status</th></tr></thead><tbody>
@forelse($rows as $row)
<tr>
    <td><a href="{{ route('requisitions.show', $row) }}">{{ $row->number }}</a></td>
    <td>{{ $row->title }}</td>
    <td>{{ $row->requester?->name }}</td>
    <td>{{ $row->department?->name }}</td>
    <td>{{ money($row->amount) }}</td>
    <td>{!! status_badge($row->status) !!}</td>
</tr>
@empty<tr><td colspan="6" class="text-muted">No requisitions.</td></tr>@endforelse
</tbody></table><div class="card-body">{{ $rows->links() }}</div></div>
@endsection
