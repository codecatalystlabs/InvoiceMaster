@php
    $title = $title ?? 'LEDGER';
@endphp
<html>
<head>
<style>
@page { margin: 20px 24px; }
body { font-family: "Times New Roman", Times, serif; font-size: 12px; color: #111; margin: 0; }
table { border-collapse: collapse; width: 100%; }
.header-table td { vertical-align: top; padding: 0; }
.logo-img { max-width: 220px; max-height: 90px; }
.logo-tagline { font-size: 11px; font-weight: bold; margin-top: 4px; font-family: Arial, sans-serif; }
.title-block { text-align: center; padding-top: 5px; }
.cashbook-badge { display: inline-block; background: #111; color: #fff; padding: 7px 26px; font-size: 24px; font-weight: bold; letter-spacing: 2px; font-family: Georgia, serif; margin-bottom: 6px; }
.title-tagline { font-style: italic; font-weight: bold; font-size: 12px; margin-top: 3px; line-height: 1.3; color: #1f5fa8; }
.contact-block { font-size: 11px; border-left: 1px solid #333; padding-left: 12px; }
.contact-block .crow { padding-bottom: 5px; }
.header-rule { border-bottom: 2px dashed #999; padding-bottom: 8px; margin-bottom: 6px; }
.subheader-table td { vertical-align: middle; padding: 3px 0; }
.debit-label { font-size: 19px; font-weight: bold; letter-spacing: 1px; font-family: Georgia, serif; }
.page-no { color: #b23b1f; font-size: 17px; font-weight: bold; padding-left: 26px; font-family: Georgia, serif; }
.credit-label { font-size: 19px; font-weight: bold; letter-spacing: 2px; text-align: right; font-family: Georgia, serif; }
.ledger th, .ledger td { border: 1px solid #333; font-size: 11px; text-align: center; padding: 4px 3px; }
.ledger th { font-family: Georgia, serif; font-weight: bold; font-size: 11px; padding: 5px 3px; }
.ledger td { height: 22px; }
.money { font-family: "Courier New", Courier, monospace; font-weight: 600; }
.ledger td.divider, .ledger th.divider { background: #222; border: none; padding: 0; width: 5px; }
.page-break { page-break-after: always; }
</style>
</head>
<body>
@foreach($pages as $index => $pageRows)
@if($index > 0)
<div class="page-break"></div>
@endif
<table class="header-table header-rule"><tr>
    <td style="width:26%;">
        @if($company->logoDataUri())
            <img class="logo-img" src="{{ $company->logoDataUri() }}"><br>
        @endif
        <div class="logo-tagline">{{ $company->taglineText() }}</div>
    </td>
    <td style="width:42%;" class="title-block">
        <div class="cashbook-badge">{{ $title }}</div>
        <div class="title-tagline">{!! nl2br(e($company->servicesText())) !!}</div>
    </td>
    <td style="width:32%;" class="contact-block">
        <div class="crow">{{ $company->phoneLine() }}</div>
        <div class="crow">{!! nl2br(e($company->addressText())) !!}</div>
        <div class="crow">{!! nl2br(e($company->emailLines())) !!}</div>
    </td>
</tr></table>
<table class="subheader-table"><tr>
    <td style="width:50%;"><span class="debit-label">DEBIT</span><span class="page-no">{{ str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT) }}</span></td>
    <td style="width:50%;" class="credit-label">CREDIT</td>
</tr></table>
<table class="ledger">
    <colgroup>
        <col style="width:6%;"><col style="width:27%;"><col style="width:6%;"><col style="width:8%;"><col style="width:8%;">
        <col style="width:0.5%;">
        <col style="width:6%;"><col style="width:27%;"><col style="width:6%;"><col style="width:8%;"><col style="width:8%;">
    </colgroup>
    <thead><tr>
        <th>DATE</th><th>PARTICULARS</th><th>FOLIO</th><th>DISCOUNT<br>ALLOWED</th><th>CASH</th>
        <th class="divider"></th>
        <th>DATE</th><th>PARTICULARS</th><th>FOLIO</th><th>DISCOUNT<br>RECEIVED</th><th>CASH</th>
    </tr></thead>
    <tbody>
        @foreach($pageRows as $row)
            @php $debit = $row['debit'] ?? null; $credit = $row['credit'] ?? null; @endphp
            <tr>
                <td>{{ $debit['date'] ?? '' }}</td>
                <td style="text-align:left;">{{ $debit['particulars'] ?? '' }}</td>
                <td>{{ $debit['folio'] ?? '' }}</td>
                <td>{{ $debit['discount'] ?? '' }}</td>
                <td>{{ $debit['cash'] ?? '' }}</td>
                <td class="divider"></td>
                <td>{{ $credit['date'] ?? '' }}</td>
                <td style="text-align:left;">{{ $credit['particulars'] ?? '' }}</td>
                <td>{{ $credit['folio'] ?? '' }}</td>
                <td>{{ $credit['discount'] ?? '' }}</td>
                <td>{{ $credit['cash'] ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
@endforeach
</body>
</html>
