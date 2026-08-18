@extends('layouts.app')
@section('title', 'Statements · '.$bank->name)
@section('content')
<a href="{{ route('banks.index') }}" class="btn btn-secondary mb-3">Back</a>
<div class="card mb-3">
    <div class="card-header">Upload statement</div>
    <div class="card-body">
        <p class="mb-2">Choose the Equity (or other bank) PDF, or a CSV. Credits come in as money in; debits as money out.</p>
        <form method="POST" action="{{ route('banks.import', $bank) }}" enctype="multipart/form-data" class="d-flex gap-2 flex-wrap align-items-center">
            @csrf
            <input type="file" name="file" class="form-control" style="max-width:420px" accept=".pdf,.csv,.txt,application/pdf,text/csv" required>
            <button class="btn btn-primary">Import</button>
        </form>
        <p class="text-muted small mb-0 mt-2">Digital PDFs only (text you can select). Scanned photos of statements will not import.</p>
    </div>
</div>
@forelse($imports as $import)
<div class="card mb-3"><div class="card-header">{{ $import->filename }} · {{ $import->line_count }} lines · {{ $import->created_at?->format('d M Y H:i') }}</div>
<div class="table-responsive"><table class="table mb-0">
<thead><tr><th>Date</th><th>Description</th><th>Ref</th><th class="text-end">Money in</th><th class="text-end">Money out</th><th>Match</th></tr></thead>
<tbody>
@foreach($import->lines as $line)
<tr>
    <td>{{ $line->line_date?->format('d M Y') }}</td>
    <td>{{ $line->description }}</td>
    <td>{{ $line->reference }}</td>
    <td class="text-end">{{ $line->amount > 0 ? money($line->amount) : '' }}</td>
    <td class="text-end">{{ $line->amount < 0 ? money(abs($line->amount)) : '' }}</td>
    <td>{{ $line->status }}{{ $line->match_type ? ' · '.$line->match_type.' #'.$line->match_id : '' }}</td>
</tr>
@endforeach
</tbody></table></div></div>
@empty
<p class="text-muted">No statements imported yet.</p>
@endforelse
@endsection
