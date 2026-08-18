@php
    $entry = $entry ?? null;
    $company = $company ?? ($entry?->company ?? auth()->user()->company);
    $sheetId = $sheetId ?? ($entry?->id ?? 'new');
    $title = $title ?? 'CASH BOOK';
    $digits = preg_replace('/\D/', '', (string) ($entry?->number ?? ''));
    $pageNo = $pageNo ?? (trim((string) ($entry?->folio ?? '')) ?: ($digits !== '' ? str_pad(substr($digits, -3), 3, '0', STR_PAD_LEFT) : ''));
    $folio = $entry?->folio ?? '';
    if ($folio === '' && $digits !== '') {
        $folio = substr($digits, -4);
    }

    if (! isset($rows)) {
        $isDebit = ($entry->type ?? 'debit') === 'debit';
        $cell = [
            'date' => $entry?->entry_date?->format('d/m/y'),
            'particulars' => $entry?->description ?? '',
            'folio' => $folio,
            'discount' => (float) ($entry?->discount_allowed ?? 0) > 0 ? number_format((float) $entry?->discount_allowed) : '',
            'cash' => money($entry?->amount ?? 0, $company),
        ];
        $rows = [[
            'debit' => $isDebit ? $cell : null,
            'credit' => $isDebit ? null : $cell,
        ]];
    }

    $rows = collect($rows)->values();
    while ($rows->count() < 18) {
        $rows->push(['debit' => null, 'credit' => null]);
    }
@endphp
<div class="cb-sheet-wrap" id="cbWrap-{{ $sheetId }}">
    <div class="cb-sheet" id="cbSheet-{{ $sheetId }}">
        <div class="cb-header">
            <div class="cb-logo-block">
                <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }}">
                <div class="cb-logo-tagline">{{ $company->taglineText() }}</div>
            </div>
            <div class="cb-title-block">
                <div class="cb-badge">{{ $title }}</div>
                <div class="cb-tagline">{!! nl2br(e($company->servicesText())) !!}</div>
            </div>
            <div class="cb-contact">
                <div>
                    <span class="cb-icon">
                        <svg viewBox="0 0 24 24"><path d="M4 3h4l2 5-2.5 1.5a11 11 0 0 0 5 5L14 12l5 2v4a2 2 0 0 1-2 2c-8 0-15-7-15-15a2 2 0 0 1 2-2z"/></svg>
                    </span>
                    {{ $company->phoneLine() }}
                </div>
                <div>
                    <span class="cb-icon">
                        <svg viewBox="0 0 24 24"><path d="M12 21s7-6.5 7-12a7 7 0 0 0-14 0c0 5.5 7 12 7 12z"/><circle cx="12" cy="9" r="2.5"/></svg>
                    </span>
                    {!! nl2br(e($company->addressText())) !!}
                </div>
                <div>
                    <span class="cb-icon">
                        <svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="1.5"/><path d="M3 6l9 7 9-7"/></svg>
                    </span>
                    {!! nl2br(e($company->emailLines())) !!}
                </div>
            </div>
        </div>
        <div class="cb-subheader">
            <div class="cb-left-sub">
                <span class="cb-debit-label">DEBIT</span>
                <span class="cb-page-no">{{ $pageNo }}</span>
            </div>
            <span class="cb-credit-label">CREDIT</span>
        </div>
        <table>
            <colgroup>
                <col class="cb-date"><col class="cb-particulars"><col class="cb-folio"><col class="cb-discount"><col class="cb-cash">
                <col class="cb-divider">
                <col class="cb-date"><col class="cb-particulars"><col class="cb-folio"><col class="cb-discount"><col class="cb-cash">
            </colgroup>
            <thead>
                <tr>
                    <th>DATE</th><th>PARTICULARS</th><th>FOLIO</th><th>DISCOUNT<br>ALLOWED</th><th>CASH</th>
                    <th class="cb-divider"></th>
                    <th>DATE</th><th>PARTICULARS</th><th>FOLIO</th><th>DISCOUNT<br>RECEIVED</th><th>CASH</th>
                </tr>
            </thead>
            <tbody>
                @foreach($rows as $row)
                    @php $debit = $row['debit'] ?? null; $credit = $row['credit'] ?? null; @endphp
                    <tr>
                        <td>{{ $debit['date'] ?? '' }}</td>
                        <td class="cb-particulars-cell">{{ $debit['particulars'] ?? '' }}</td>
                        <td>{{ $debit['folio'] ?? '' }}</td>
                        <td>{{ $debit['discount'] ?? '' }}</td>
                        <td class="cb-cash-cell">{{ $debit['cash'] ?? '' }}</td>
                        <td class="cb-divider"></td>
                        <td>{{ $credit['date'] ?? '' }}</td>
                        <td class="cb-particulars-cell">{{ $credit['particulars'] ?? '' }}</td>
                        <td>{{ $credit['folio'] ?? '' }}</td>
                        <td>{{ $credit['discount'] ?? '' }}</td>
                        <td class="cb-cash-cell">{{ $credit['cash'] ?? '' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
