@extends('layouts.app')
@section('title', $requisition->number)
@section('content')
@php $canReview = \App\Models\Requisition::reviewerCanAct(auth()->user(), $requisition); @endphp
<div class="d-flex justify-content-between mb-3"><a href="{{ route('requisitions.index') }}" class="btn btn-secondary">Back</a></div>
<div class="row g-3">
<div class="col-md-7">
    <div class="card mb-3"><div class="card-header">{{ $requisition->title }}</div><div class="card-body">
        <p>{!! status_badge($requisition->status) !!} · {{ money($requisition->amount) }} issued · {{ $requisition->requester?->name }} · {{ $requisition->department?->name }}</p>
        <p>{{ $requisition->purpose }}</p>
        @if($requisition->allocation)<p class="mb-1">Budget: {{ $requisition->allocation->name }} ({{ money($requisition->allocation->available($requisition->id)) }} still free after this request)</p>@endif
        @if($requisition->fund)<p class="mb-1">Tin: {{ $requisition->fund->name }} · {{ money($requisition->fund->balance) }} on hand</p>@endif
        @if($requisition->status === 'accounted')
            <p class="mb-0">Spent {{ money($requisition->accounted_amount) }} · return {{ money($requisition->remainder()) }}</p>
        @endif
        @if($requisition->reject_reason)<p class="text-danger mb-0">Rejected: {{ $requisition->reject_reason }}</p>@endif
    </div></div>
    @if($requisition->status === 'disbursed' && ($requisition->user_id === auth()->id() || $canReview))
    <form method="POST" action="{{ route('requisitions.account', $requisition) }}" enctype="multipart/form-data" class="card card-body mb-3">@csrf
        <h6>Accountability</h6>
        <p class="text-muted small">Fill only the rows you used. Spend cannot exceed {{ money($requisition->amount) }}. Empty rows are ignored. Unspent cash returns to the tin when a reviewer accepts this.</p>
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
        <ol class="small text-muted ps-3 mb-3">
            <li>Submit</li>
            <li>Initiate (optional) then Approve</li>
            <li>{{ $requisition->type === 'petty_cash' ? 'Issue from the tin' : 'Mark as issued' }}</li>
            <li>Account for spend</li>
            <li>Accept — expense posted, unspent returned</li>
        </ol>
        @if($canReview && $requisition->status === 'submitted')
            <form method="POST" action="{{ route('requisitions.initiate', $requisition) }}" class="mb-2">@csrf
                <input name="notes" class="form-control mb-2" placeholder="Optional note">
                <button class="btn btn-primary w-100">Initiate</button>
            </form>
        @endif
        @if($canReview && in_array($requisition->status, ['submitted','initiated']))
            <form method="POST" action="{{ route('requisitions.approve', $requisition) }}" class="mb-2">@csrf
                <input name="notes" class="form-control mb-2" placeholder="Optional note">
                <button class="btn btn-success w-100">Approve</button>
            </form>
        @endif
        @if((can_module('petty-cash') || $canReview) && $requisition->status === 'approved')
            <form method="POST" action="{{ route('requisitions.disburse', $requisition) }}" class="mb-2">@csrf
                @if($requisition->type === 'petty_cash')
                    <select name="petty_cash_fund_id" class="form-select mb-2" required>
                        @foreach($funds as $f)<option value="{{ $f->id }}" @selected($requisition->petty_cash_fund_id==$f->id)>{{ $f->name }} · {{ money($f->balance) }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary w-100">Issue petty cash</button>
                @else
                    <button class="btn btn-primary w-100">Mark as issued</button>
                @endif
            </form>
        @endif
        @if($canReview && $requisition->status === 'accounted')
            <form method="POST" action="{{ route('requisitions.close', $requisition) }}" class="mb-2">@csrf
                <input name="notes" class="form-control mb-2" placeholder="Optional note">
                <button class="btn btn-success w-100">Accept accountability</button>
            </form>
        @endif
        @if($canReview && !in_array($requisition->status, ['rejected','closed']))
            <form method="POST" action="{{ route('requisitions.reject', $requisition) }}" data-confirm="Reject this requisition?">@csrf
                @if(in_array($requisition->status, ['disbursed','accounted']) && $requisition->type === 'petty_cash')
                    <p class="small text-warning">Rejecting now returns {{ money($requisition->amount) }} to the tin. No expense is posted.</p>
                @endif
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
