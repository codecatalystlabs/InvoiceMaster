@extends('layouts.app')
@section('title', $requisition->number)
@section('content')
<div class="d-flex justify-content-between mb-3"><a href="{{ route('requisitions.index') }}" class="btn btn-secondary">Back</a></div>
<div class="row g-3">
<div class="col-md-7">
    <div class="card mb-3"><div class="card-header">{{ $requisition->title }}</div><div class="card-body">
        <p>{!! status_badge($requisition->status) !!} · {{ money($requisition->amount) }} · {{ $requisition->requester?->name }} · {{ $requisition->department?->name }}</p>
        <p>{{ $requisition->purpose }}</p>
        @if($requisition->allocation)<p class="mb-0">Allocation: {{ $requisition->allocation->name }}</p>@endif
    </div></div>
    @if($requisition->status === 'disbursed' && ($requisition->user_id === auth()->id() || can_module('requisitions.review')))
    <form method="POST" action="{{ route('requisitions.account', $requisition) }}" enctype="multipart/form-data" class="card card-body mb-3">@csrf
        <h6>Accountability</h6>
        <p class="text-muted small">Show how the issued cash was spent. Unspent cash is returned when a reviewer closes this.</p>
        @for($i=0;$i<4;$i++)
        <div class="row g-2 mb-2">
            <div class="col-md-5"><input name="lines[{{ $i }}][description]" class="form-control" placeholder="What it was spent on"></div>
            <div class="col-md-2"><input type="number" step="0.01" name="lines[{{ $i }}][amount]" class="form-control" placeholder="Amount"></div>
            <div class="col-md-2"><input type="date" name="lines[{{ $i }}][spent_on]" class="form-control"></div>
            <div class="col-md-3"><input type="file" name="lines[{{ $i }}][receipt]" class="form-control"></div>
        </div>
        @endfor
        <textarea name="notes" class="form-control mb-2" placeholder="Notes"></textarea>
        <button class="btn btn-primary">Submit accountability</button>
    </form>
    @endif
    @if($requisition->lines->count())
    <div class="card"><div class="card-header">Accounted lines · {{ money($requisition->accounted_amount) }}</div>
    <table class="table mb-0"><thead><tr><th>Date</th><th>Detail</th><th>Amount</th><th>Receipt</th></tr></thead><tbody>
    @foreach($requisition->lines as $line)
        <tr>
            <td>{{ $line->spent_on?->format('d M Y') }}</td>
            <td>{{ $line->description }}</td>
            <td>{{ money($line->amount) }}</td>
            <td>@if($line->receipt_path)<a href="{{ asset('storage/'.$line->receipt_path) }}" target="_blank">File</a>@endif</td>
        </tr>
    @endforeach
    </tbody></table></div>
    @endif
</div>
<div class="col-md-5">
    <div class="card mb-3"><div class="card-header">Workflow</div><div class="card-body">
        @if(can_module('requisitions.review') && $requisition->status === 'submitted')
            <form method="POST" action="{{ route('requisitions.initiate', $requisition) }}" class="mb-2">@csrf<button class="btn btn-primary w-100">Initiate</button></form>
        @endif
        @if(can_module('requisitions.review') && in_array($requisition->status, ['submitted','initiated']))
            <form method="POST" action="{{ route('requisitions.approve', $requisition) }}" class="mb-2">@csrf<button class="btn btn-success w-100">Approve</button></form>
        @endif
        @if(can_module('petty-cash') && $requisition->status === 'approved')
            <form method="POST" action="{{ route('requisitions.disburse', $requisition) }}" class="mb-2">@csrf
                <select name="petty_cash_fund_id" class="form-select mb-2">
                    @foreach($funds as $f)<option value="{{ $f->id }}" @selected($requisition->petty_cash_fund_id==$f->id)>{{ $f->name }} · {{ money($f->balance) }}</option>@endforeach
                </select>
                <button class="btn btn-primary w-100">Issue petty cash</button>
            </form>
        @endif
        @if(can_module('requisitions.review') && $requisition->status === 'accounted')
            <form method="POST" action="{{ route('requisitions.close', $requisition) }}" class="mb-2">@csrf<button class="btn btn-success w-100">Accept accountability</button></form>
        @endif
        @if(can_module('requisitions.review') && !in_array($requisition->status, ['rejected','closed']))
            <form method="POST" action="{{ route('requisitions.reject', $requisition) }}">@csrf
                <textarea name="reject_reason" class="form-control mb-2" placeholder="Reason" required></textarea>
                <button class="btn btn-outline-danger w-100">Reject</button>
            </form>
        @endif
    </div></div>
    <div class="card"><div class="card-header">Trail</div>
    <ul class="list-group list-group-flush">
        @forelse($requisition->steps as $step)
            <li class="list-group-item"><strong>{{ $step->step }}</strong> · {{ $step->user?->name }} · {{ $step->created_at?->format('d M H:i') }}<div class="small text-muted">{{ $step->notes }}</div></li>
        @empty<li class="list-group-item text-muted">No steps yet.</li>@endforelse
    </ul></div>
</div>
</div>
@endsection
