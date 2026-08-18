<html>
<head>
<style>
@page { margin: 24px 34px; }
body { font-family: Georgia, "Times New Roman", serif; font-size: 13px; color: #111; margin: 0; }
table { border-collapse: collapse; width: 100%; }
.header-table td { vertical-align: top; }
.logo-img { max-width: 220px; max-height: 90px; }
.tagline { font-family: Arial, sans-serif; font-weight: bold; font-size: 10px; letter-spacing: 1px; margin-top: 6px; }
.contact { font-family: Arial, sans-serif; font-size: 10px; text-align: right; line-height: 1.5; border-left: 1px solid #333; padding-left: 14px; }
.services { text-align: center; color: #1f5fa8; font-weight: bold; font-style: italic; font-family: Arial, sans-serif; font-size: 12px; margin-top: 8px; line-height: 1.4; }
.thickbar { height: 3px; background: #111; margin: 6px 0 14px 0; }
.top-row td { vertical-align: middle; padding: 4px 0; }
.no-box, .date-box { border: 2px solid #111; }
.no-box td, .date-box td { padding: 6px 10px; font-size: 15px; }
.no-label, .date-label { background: #111; color: #fff; font-size: 15px; white-space: nowrap; }
.no-box .no-label { background: #fff; color: #111; border-right: 2px solid #111; }
.no-value { color: #c0392b; font-style: italic; font-size: 16px; text-align: center; }
.receipt-pill { background: #111; color: #fff; font-family: Arial, sans-serif; font-weight: bold; font-size: 15px; letter-spacing: 2px; text-align: center; padding: 10px 22px; border: 2px solid #111; border-radius: 6px; }
.field-line { font-size: 14px; margin-bottom: 18px; }
.field-label { white-space: nowrap; padding-right: 6px; }
.field-fill { border-bottom: 1.3px solid #111; padding-bottom: 2px; }
.shs-box { border: 2px solid #111; width: 220px; height: 40px; text-align: center; vertical-align: middle; font-size: 16px; font-weight: bold; color: #c0392b; }
.with-thanks { font-style: italic; font-size: 14px; text-align: center; padding-top: 4px; }
.signature-fill { border-bottom: 1.3px solid #111; width: 180px; }
.for-line { text-align: right; font-family: Arial, sans-serif; font-weight: bold; font-size: 10px; margin-top: 14px; }
</style>
</head>
<body>
<table class="header-table"><tr>
    <td style="width:55%;">
        @if($company->logoDataUri())
            <img class="logo-img" src="{{ $company->logoDataUri() }}"><br>
        @endif
        <div class="tagline">{{ $company->taglineText() }}</div>
    </td>
    <td class="contact" style="width:45%;">
        {!! nl2br(e($company->phoneLine())) !!}<br>
        {!! nl2br(e($company->addressText())) !!}<br>
        {!! nl2br(e($company->emailLines())) !!}
    </td>
</tr></table>
<div class="services">{!! nl2br(e($company->servicesText())) !!}</div>
<div class="thickbar"></div>
<table class="top-row"><tr>
    <td style="width:33%;">
        <table class="no-box"><tr>
            <td class="no-label">No.</td>
            <td class="no-value">{{ $receipt->number }}</td>
        </tr></table>
    </td>
    <td style="width:34%; text-align:center;">
        <span class="receipt-pill">RECEIPT</span>
    </td>
    <td style="width:33%;">
        <table class="date-box"><tr>
            <td class="date-label">Date</td>
            <td>{{ $receipt->issued_date?->format('d M Y') }}</td>
        </tr></table>
    </td>
</tr></table>
<table style="margin-top:22px;">
    <tr class="field-line"><td class="field-label">Received with thanks from</td><td class="field-fill">{{ $receipt->client_name }}</td></tr>
    <tr class="field-line"><td class="field-label">Telephone</td><td class="field-fill">{{ $receipt->client_contact }}</td></tr>
    <tr class="field-line"><td class="field-label" style="width:230px;">The amount of&nbsp;Shillings Shs/USD</td><td class="field-fill">{{ money($receipt->amount, $company) }}</td></tr>
    <tr class="field-line"><td class="field-label">Being payment of</td><td class="field-fill">{{ $receipt->description }}</td></tr>
    <tr class="field-line"><td class="field-label">Cash/Cheque No.</td><td class="field-fill">{{ $receipt->reference_no }}</td></tr>
    @if($receipt->balance !== null && $receipt->balance !== '')
    <tr class="field-line"><td class="field-label">Balance</td><td class="field-fill">{{ $receipt->balance }}</td></tr>
    @endif
</table>
<table style="margin-top:16px;"><tr>
    <td style="width:40%; text-align:center;">
        <table style="margin:0 auto;"><tr><td class="shs-box">{{ money($receipt->amount, $company) }}</td></tr></table>
        <div class="with-thanks">With thanks</div>
    </td>
    <td style="width:30%;"></td>
    <td style="width:30%; vertical-align:bottom;">
        Signature: <span class="signature-fill">&nbsp;</span>
    </td>
</tr></table>
<div class="for-line">for; {{ $company->displayNameUpper() }}</div>
</body>
</html>
