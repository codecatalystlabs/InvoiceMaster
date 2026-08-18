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
.services { text-align: center; color: #1558b0; font-weight: bold; font-style: italic; font-family: Arial, sans-serif; font-size: 12px; margin-top: 8px; line-height: 1.4; }
.thickbar { height: 3px; background: #111; margin: 6px 0 14px 0; }
.top-row td { vertical-align: middle; padding: 4px 0; }
.no-box, .date-box { border: 2px solid #111; }
.no-box td, .date-box td { padding: 6px 10px; font-size: 15px; }
.no-label { background: #fff; color: #111; border-right: 2px solid #111; font-size: 15px; white-space: nowrap; }
.date-label { background: #111; color: #fff; font-size: 15px; white-space: nowrap; }
.no-value { color: #c0392b; font-style: italic; font-size: 16px; text-align: center; }
.receipt-pill { background: #111; color: #fff; font-family: Arial, sans-serif; font-weight: bold; font-size: 15px; letter-spacing: 2px; text-align: center; padding: 10px 22px; border: 2px solid #111; border-radius: 6px; }
.field-label { white-space: nowrap; padding-right: 6px; font-size: 14px; }
.field-fill { border-bottom: 1.3px solid #111; padding-bottom: 2px; font-size: 14px; }
.shs-box { border: 2px solid #111; width: 240px; height: 42px; text-align: center; vertical-align: middle; font-size: 16px; font-weight: bold; color: #c0392b; }
.money { font-family: "Courier New", Courier, monospace; font-weight: 600; }
.with-thanks { font-style: italic; font-size: 14px; text-align: center; padding-top: 4px; }
.signature-fill { border-bottom: 1.3px solid #111; display: inline-block; min-width: 180px; padding: 0 6px 2px; font-weight: bold; }
.for-line { text-align: right; font-family: Arial, sans-serif; font-weight: bold; font-size: 10px; margin-top: 14px; }
.shs-label { font-size: 13px; }
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
            <td class="no-value">{{ $receipt->shortNumber() }}</td>
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
    <tr><td class="field-label">Received with thanks from</td><td class="field-fill">{{ $receipt->client_name }}</td></tr>
    <tr><td class="field-label">Telephone</td><td class="field-fill">{{ $receipt->client_contact }}</td></tr>
    <tr><td class="field-label" style="width:250px;">The amount of Shillings Shs/USD</td><td class="field-fill">{{ money($receipt->amount, $company) }}</td></tr>
    <tr><td class="field-label">Being payment of</td><td class="field-fill">{{ $receipt->description }}</td></tr>
</table>
<table style="margin-top:10px;">
    <tr>
        <td class="field-label" style="width:130px;">Cash/Cheque No.</td>
        <td class="field-fill" style="width:35%;">{{ $receipt->reference_no }}</td>
        <td class="field-label" style="width:80px; padding-left:20px;">Balance:</td>
        <td class="field-fill">{{ $receipt->balance }}</td>
    </tr>
</table>
<table style="margin-top:16px;"><tr>
    <td style="width:18%;" class="shs-label">SHS/USD</td>
    <td style="width:42%; text-align:center;">
        <table style="margin:0 auto;"><tr><td class="shs-box">{{ money($receipt->amount, $company) }}</td></tr></table>
        <div class="with-thanks">With thanks</div>
    </td>
    <td style="width:40%; vertical-align:bottom;">
        Served by: <span class="signature-fill">{{ $receipt->servedByName() }}</span>
    </td>
</tr></table>
<div class="for-line">for; {{ $company->displayNameUpper() }}</div>
</body>
</html>
