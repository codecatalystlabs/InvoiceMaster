<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    @include('documents.partials.styles')
</head>
<body>
@include('documents.partials.header')
@php
    $client = $quotation->client;
@endphp

<div class="doc-title">QUOTATION</div>

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
                <div class="info-label">TO:</div>
                <div class="info-value">
                    <strong>{{ $client?->name }}</strong>
                    @if($client?->company)<br>{{ $client->company }}@endif
                    @if($client?->email)<br>{{ $client->email }}@endif
                    @if($client?->phone)<br>{{ $client->phone }}@endif
                </div>
            </div>
        </td>
    </tr>
</table>

<table class="meta-table">
    <tr>
        <td style="width:50%">
            <div class="info-label">Quotation Number:</div>
            <div class="info-value">{{ $quotation->quotation_number }}</div>
        </td>
        <td style="width:50%">
            <div class="info-label">Date:</div>
            <div class="info-value">{{ $quotation->date?->format('M d, Y') }}</div>
        </td>
    </tr>
</table>

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
    @forelse($quotation->items as $item)
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
            <td style="text-align:right">{{ money($quotation->subtotal, $company) }}</td>
        </tr>
        <tr>
            <td colspan="3" style="text-align:right">Tax:</td>
            <td style="text-align:right">{{ money($quotation->tax, $company) }}</td>
        </tr>
        @if((float) $quotation->discount > 0)
        <tr>
            <td colspan="3" style="text-align:right">Discount:</td>
            <td style="text-align:right">-{{ money($quotation->discount, $company) }}</td>
        </tr>
        @endif
        <tr class="total-row">
            <td colspan="3" style="text-align:right">TOTAL:</td>
            <td style="text-align:right">{{ money($quotation->total, $company) }}</td>
        </tr>
    </tfoot>
</table>

@if($quotation->notes)
<div style="margin-top:22px">
    <div class="info-label">Notes:</div>
    <div class="info-value">{!! nl2br(e($quotation->notes)) !!}</div>
</div>
@endif

<div class="footer">
    Thank you for your business!<br>
    This quotation is valid for 30 days from the date of issue.
</div>
</body>
</html>
