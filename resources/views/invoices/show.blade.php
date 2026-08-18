@extends('layouts.app')
@section('title', $invoice->invoice_number)
@section('content')
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Back</a>
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline-danger">Download PDF</a>
        <a href="{{ $invoice->payUrl() }}" class="btn btn-outline-primary" target="_blank">Payment link</a>
        <a href="{{ route('invoices.docx', $invoice) }}" class="btn btn-outline-primary">DOCX</a>
        <a href="{{ route('invoices.email', $invoice) }}" class="btn btn-outline-success">Email</a>
        @if($invoice->isOpen() && can_module('receivables'))
            <form method="POST" action="{{ route('receivables.remind', $invoice) }}">@csrf<button class="btn btn-outline-success">Remind</button></form>
        @endif
        @if(can_module('efris'))
        <form method="POST" action="{{ route('invoices.efris', $invoice) }}">@csrf<button class="btn btn-outline-secondary">Queue EFRIS</button></form>
        @endif
        <a href="{{ route('invoices.edit', $invoice) }}" class="btn btn-primary">Edit</a>
        @if($invoice->isOpen() && $invoice->outstanding() > 0)
        <form method="POST" action="{{ route('invoices.paid', $invoice) }}">@csrf<button class="btn btn-success">Mark paid</button></form>
        @endif
        <form method="POST" action="{{ route('invoices.destroy', $invoice) }}" data-confirm="Delete invoice {{ $invoice->invoice_number }}? This cannot be undone.">@csrf @method('DELETE')
            <button class="btn btn-outline-danger">Delete</button>
        </form>
    </div>
</div>
<iframe class="pdf-frame" src="{{ route('invoices.pdf', $invoice) }}?inline=1&t={{ optional($invoice->updated_at)->timestamp }}" title="Invoice {{ $invoice->invoice_number }}"></iframe>
@endsection
