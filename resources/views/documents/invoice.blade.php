<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('documents.partials.styles')
</head>
<body>
@include('documents.partials.header')
@php
    $statusColors = [
        'Unpaid' => '#ffc107', 'unpaid' => '#ffc107',
        'Paid' => '#28a745', 'paid' => '#28a745',
        'Overdue' => '#dc3545', 'overdue' => '#dc3545',
        'Partially Paid' => '#17a2b8',
        'Cancelled' => '#6c757d', 'cancelled' => '#6c757d',
        'proforma' => '#0d6efd', 'draft' => '#6c757d', 'sent' => '#fd7e14',
    ];
    $statusColor = $statusColors[$invoice->status] ?? '#6c757d';
    $client = $invoice->client;
    $billName = $invoice->displayClient();
    $billCompany = $client?->company;
    $billEmail = $client?->email;
    $billPhone = $client?->phone ?: $invoice->client_contact;
    $isPaid = in_array(strtolower((string) $invoice->status), ['paid'], true);
@endphp

<div class="doc-title">
    INVOICE
    <span class="status-badge" style="background-color: {{ $statusColor }}">{{ $invoice->status }}</span>
</div>

<table class="party-table">
    <tr>
        <td>
            <div class="info-section">
                <div class="info-label">FROM:</div>
                <div class="info-value">
                    <strong>{{ $company->name }}</strong><br>
                    {!! nl2br(e($company->address ?: $company->addressText())) !!}<br>
                    {{ $company->email }}<br>
                    {{ $company->phone }}
                </div>
            </div>
        </td>
        <td>
            <div class="info-section">
                <div class="info-label">BILL TO:</div>
                <div class="info-value">
                    <strong>{{ $billName }}</strong>
                    @if($billCompany)<br>{{ $billCompany }}@endif
                    @if($billEmail)<br>{{ $billEmail }}@endif
                    @if($billPhone)<br>{{ $billPhone }}@endif
                </div>
            </div>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td style="width:33%">
            <div class="info-label">Invoice Number:</div>
            <div class="info-value">{{ $invoice->invoice_number }}</div>
        </td>
        <td style="width:33%">
            <div class="info-label">Invoice Date:</div>
            <div class="info-value">{{ $invoice->date?->format('M d, Y') }}</div>
        </td>
        <td style="width:34%">
            <div class="info-label">Due Date:</div>
            <div class="info-value">{{ $invoice->due_date?->format('M d, Y') }}</div>
        </td>
    </tr>
</table>

@if(! $isPaid)
<div class="due-date-box">
    <strong>Amount Due: {{ money($invoice->total, $company) }}</strong><br>
    <span style="font-size:9pt">Payment Due By: {{ $invoice->due_date?->format('M d, Y') }}</span>
</div>
@endif

<table class="items-table">
    <thead>
        <tr>
            <th style="width:45%">Item Name</th>
            <th style="width:15%; text-align:center">Quantity</th>
            <th style="width:20%; text-align:right">Unit Price</th>
            <th style="width:20%; text-align:right">Total</th>
        </tr>
    </thead>
    <tbody>
    @forelse($invoice->items as $item)
        <tr>
            <td>{{ $item->item_name }}</td>
            <td style="text-align:center">{{ $item->qty }}</td>
            <td style="text-align:right">{{ money($item->unit_price, $company) }}</td>
            <td style="text-align:right">{{ money($item->total, $company) }}</td>
        </tr>
    @empty
        <tr><td colspan="4">No line items</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3" style="text-align:right">Subtotal:</td>
            <td style="text-align:right">{{ money($invoice->subtotal, $company) }}</td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:right">Tax:</td>
            <td style="text-align:right">{{ money($invoice->tax, $company) }}</td>
        </tr>
        @if((float) $invoice->discount > 0)
        <tr>
            <td colspan="3" style="text-align:right">Discount:</td>
            <td style="text-align:right">-{{ money($invoice->discount, $company) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td colspan="3" style="text-align:right">AMOUNT DUE:</td>
            <td style="text-align:right">{{ money($invoice->total, $company) }}</td>
        </tr>
    </tfoot>
</table>

@if(! $isPaid && $invoice->due_date)
<div class="payment-notice">
    <strong>Payment Due:</strong> {{ money($invoice->total, $company) }} by {{ $invoice->due_date->format('M d, Y') }}<br>
    Please ensure payment is received by the due date to avoid any late fees.
</div>
@endif

@if($invoice->notes)
<div style="margin-top:22px">
    <div class="info-label">Notes:</div>
    <div class="info-value">{!! nl2br(e($invoice->notes)) !!}</div>
</div>
@endif

<div class="footer">
    Thank you for your business!<br>
    Please make payment by the due date to avoid late fees.
</div>
</body>
</html>
