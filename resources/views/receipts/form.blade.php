@extends('layouts.app')
@section('title', $receipt->exists ? 'Edit Receipt' : 'New Receipt')
@section('content')
@php
    $company = auth()->user()->company;
    $previewNumber = $receipt->number ?: 'RCT-'.date('Y').'-····';
    $methods = ['cash' => 'Cash', 'mtn_momo' => 'MTN Mobile Money', 'airtel_money' => 'Airtel Money', 'bank' => 'Bank'];
@endphp
<div class="d-flex justify-content-between align-items-baseline mb-3">
    <h2 class="h4 mb-0">{{ $receipt->exists ? 'Edit Receipt' : 'New Receipt' }}</h2>
    <a href="{{ route('receipts.index') }}" class="text-muted">&larr; Back to Receipts</a>
</div>
<div class="row g-4">
    <div class="col-lg-7">
        <div class="card"><div class="card-body">
            <form method="POST" action="{{ $receipt->exists ? route('receipts.update', $receipt) : route('receipts.store') }}" id="receiptForm">
                @csrf @if($receipt->exists) @method('PUT') @endif
                <div class="mb-3">
                    <label class="form-label text-uppercase small text-muted">Received with thanks from</label>
                    <input id="client_name" name="client_name" class="form-control" value="{{ old('client_name', $receipt->client_name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label text-uppercase small text-muted">Telephone</label>
                    <input id="client_contact" name="client_contact" class="form-control" value="{{ old('client_contact', $receipt->client_contact) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label text-uppercase small text-muted">Being payment of</label>
                    <input id="description" name="description" class="form-control" value="{{ old('description', $receipt->description) }}">
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase small text-muted">Amount ({{ $company->currency ?: 'UGX' }})</label>
                        <input id="amount" type="number" step="0.01" min="0" name="amount" class="form-control" value="{{ old('amount', $receipt->amount) }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase small text-muted">Payment method</label>
                        <select id="payment_method" name="payment_method" class="form-select">
                            @foreach($methods as $k => $v)
                                <option value="{{ $k }}" @selected(old('payment_method', $receipt->payment_method)===$k)>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-uppercase small text-muted">Date issued</label>
                    <input id="issued_date" type="date" name="issued_date" class="form-control" value="{{ old('issued_date', optional($receipt->issued_date)->toDateString() ?? date('Y-m-d')) }}" required>
                </div>
                <div class="row g-3">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase small text-muted">Cash/Cheque No.</label>
                        <input id="reference_no" name="reference_no" class="form-control" value="{{ old('reference_no', $receipt->reference_no) }}" placeholder="Optional">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-uppercase small text-muted">Balance ({{ $company->currency ?: 'UGX' }})</label>
                        <input id="balance" type="number" step="0.01" min="0" name="balance" class="form-control" value="{{ old('balance', $receipt->balance) }}" placeholder="Optional">
                    </div>
                </div>
                <button class="btn btn-primary w-100">{{ $receipt->exists ? 'Save changes' : 'Create receipt' }}</button>
            </form>
        </div></div>
    </div>
    <div class="col-lg-5">
        <p class="text-uppercase small text-muted mb-2">Live preview</p>
        <div class="card" style="position:relative;">
            <div class="card-body">
                <div class="fw-semibold">{{ $company->name }}</div>
                <div class="text-muted small font-monospace">RECEIPT {{ $previewNumber }} · <span id="stubDate"></span></div>
                <hr>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Received with thanks from</span><strong id="stubClient">—</strong></div>
                <div class="d-flex justify-content-between mb-2" id="stubForRow" style="display:none;"><span class="text-muted">Being payment of</span><strong id="stubFor"></strong></div>
                <div class="d-flex justify-content-between mb-2"><span class="text-muted">Payment</span><strong id="stubMethod">Cash</strong></div>
                <div class="d-flex justify-content-between mb-2" id="stubRefRow" style="display:none;"><span class="text-muted">Cash/Cheque No.</span><strong id="stubRef"></strong></div>
                <div class="d-flex justify-content-between mb-2" id="stubBalanceRow" style="display:none;"><span class="text-muted">Balance</span><strong id="stubBalance"></strong></div>
                <div class="d-flex justify-content-between align-items-baseline mt-3 pt-3 border-top">
                    <span class="text-uppercase small text-muted">Total</span>
                    <span id="stubAmount" class="fs-4 fw-bold" style="color:#ffa726;">UGX 0</span>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    var currency = @json($company->currency ?: 'UGX');
    var methodLabels = { cash: 'Cash', mtn_momo: 'MTN Mobile Money', airtel_money: 'Airtel Money', bank: 'Bank' };
    var clientInput = document.getElementById('client_name');
    var descInput = document.getElementById('description');
    var amountInput = document.getElementById('amount');
    var methodSelect = document.getElementById('payment_method');
    var dateInput = document.getElementById('issued_date');
    var refInput = document.getElementById('reference_no');
    var balanceInput = document.getElementById('balance');
    function formatMoney(n) {
        var num = parseFloat(n) || 0;
        return currency + ' ' + num.toLocaleString('en-US', { maximumFractionDigits: 0 });
    }
    function formatDate(value) {
        if (!value) return '';
        var d = new Date(value + 'T00:00:00');
        if (isNaN(d.getTime())) return '';
        return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
    }
    function render() {
        document.getElementById('stubClient').textContent = clientInput.value.trim() || '—';
        if (descInput.value.trim()) {
            document.getElementById('stubForRow').style.display = 'flex';
            document.getElementById('stubFor').textContent = descInput.value.trim();
        } else {
            document.getElementById('stubForRow').style.display = 'none';
        }
        document.getElementById('stubMethod').textContent = methodLabels[methodSelect.value] || 'Cash';
        document.getElementById('stubAmount').textContent = formatMoney(amountInput.value);
        document.getElementById('stubDate').textContent = formatDate(dateInput.value);
        if (refInput.value.trim()) {
            document.getElementById('stubRefRow').style.display = 'flex';
            document.getElementById('stubRef').textContent = refInput.value.trim();
        } else {
            document.getElementById('stubRefRow').style.display = 'none';
        }
        if (balanceInput.value !== '') {
            document.getElementById('stubBalanceRow').style.display = 'flex';
            document.getElementById('stubBalance').textContent = formatMoney(balanceInput.value);
        } else {
            document.getElementById('stubBalanceRow').style.display = 'none';
        }
    }
    [clientInput, descInput, amountInput, dateInput, refInput, balanceInput].forEach(function (el) {
        el.addEventListener('input', render);
    });
    methodSelect.addEventListener('change', render);
    render();
})();
</script>
@endsection
