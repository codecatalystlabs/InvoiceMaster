@php
    $company = $company ?? $receipt->company ?? auth()->user()->company;
    $sheetId = $receipt->id ?: 'new';
@endphp
<div class="sheet-wrap" id="sheetWrap-{{ $sheetId }}">
    <div class="sheet" id="printable-{{ $sheetId }}">
        <div class="header">
            <div class="logo-block">
                <img class="logo-img" src="{{ $company->logoUrl() }}" alt="{{ $company->name }}">
                <div class="tagline">{{ $company->taglineText() }}</div>
            </div>
            <div class="contact-block">
                <div class="contact-icons">
                    <div class="icon-circle">&#9742;</div>
                    <div class="icon-circle">
                        <svg viewBox="0 0 24 24" width="14" height="14" fill="#1a73e8">
                            <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/>
                        </svg>
                    </div>
                    <div class="icon-circle">&#9993;</div>
                </div>
                <div class="contact-lines">
                    <div class="line1">{!! nl2br(e($company->phoneLine())) !!}</div>
                    <div>{!! nl2br(e($company->addressText())) !!}</div>
                    <div>{!! nl2br(e($company->emailLines())) !!}</div>
                </div>
            </div>
        </div>

        <div class="services">{!! nl2br(e($company->servicesText())) !!}</div>
        <div class="thickbar"></div>

        <div class="top-row">
            <div class="no-box">
                <div class="no-label">No.</div>
                <div class="no-value">{{ $receipt->shortNumber() }}</div>
            </div>
            <div class="receipt-pill-wrap">
                <div class="receipt-pill">RECEIPT</div>
            </div>
            <div class="date-box">
                <div class="date-label">Date</div>
                <div class="date-value">{{ $receipt->issued_date?->format('d M Y') }}</div>
            </div>
        </div>

        <div class="field-line">
            <span class="field-label">Received with thanks from</span>
            <span class="field-fill"><span class="value">{{ $receipt->client_name }}</span></span>
        </div>
        <div class="field-line telephone-row">
            <span class="field-fill short"></span>
            <span class="field-label">Telephone</span>
            <span class="field-fill"><span class="value">{{ $receipt->client_contact }}</span></span>
        </div>
        <div class="field-line">
            <span class="field-label">The amount of &nbsp;Shillings Shs/USD</span>
            <span class="field-fill"><span class="value">{{ money($receipt->amount, $company) }}</span></span>
        </div>
        <div class="field-line">
            <span class="field-label">Being payment of</span>
            <span class="field-fill"><span class="value">{{ $receipt->description }}</span></span>
        </div>
        <div class="field-line">
            <span class="field-label">Cash/Cheque No.</span>
            <span class="field-fill short"><span class="value">{{ $receipt->reference_no }}</span></span>
            <span class="field-label" style="margin-left:40px;">Balance:</span>
            <span class="field-fill"><span class="value">{{ $receipt->balance }}</span></span>
        </div>

        <div class="shs-usd-row">
            <span class="shs-usd-label">SHS/USD</span>
            <div class="shs-usd-col">
                <div class="shs-usd-box">{{ money($receipt->amount, $company) }}</div>
                <div class="with-thanks">With thanks</div>
            </div>
            <div style="flex:1;"></div>
            <span class="field-label" style="margin-right:8px;">Served by:</span>
            <span class="signature-fill"><span class="value">{{ $receipt->servedByName() }}</span></span>
        </div>
        <div class="for-line">for; <span class="company">{{ $company->displayNameUpper() }}</span></div>
    </div>
</div>
