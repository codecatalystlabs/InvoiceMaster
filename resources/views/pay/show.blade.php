@extends('layouts.public')
@section('title', 'Pay '.$invoice->invoice_number)
@section('content')
<div class="card">
    <div class="card-body">
        <h1 class="h4">Invoice {{ $invoice->invoice_number }}</h1>
        <p class="text-muted mb-1">{{ $invoice->displayClient() }}</p>
        <p>Total {{ money($invoice->total, $invoice->company) }} · Paid {{ money($invoice->amount_paid, $invoice->company) }}</p>
        <p class="fs-4 fw-semibold">Due {{ money($invoice->outstanding(), $invoice->company) }}</p>
        @if($pending)
            <div class="alert alert-info">A Mobile Money prompt is already open for {{ $pending->phone }}. <a href="{{ route('pay.wait', $invoice->pay_token) }}">Check status</a></div>
        @endif
        @if($invoice->isOpen() && $invoice->outstanding() > 0)
            <form method="POST" action="{{ route('pay.store', $invoice->pay_token) }}" class="mt-3">
                @csrf
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" min="0.01" max="{{ $invoice->outstanding() }}" name="amount" class="form-control mb-2" value="{{ $invoice->outstanding() }}" required>
                <label class="form-label">Pay with</label>
                <select name="method" id="pay-method" class="form-select mb-2">
                    <option value="mtn_momo">MTN Mobile Money</option>
                    <option value="airtel_money">Airtel Money</option>
                    <option value="bank">Bank transfer</option>
                    <option value="card">Card</option>
                    <option value="cash">Cash</option>
                </select>
                <div id="momo-fields">
                    <label class="form-label">Phone {{ $yoEnabled ? '(PIN prompt will be sent here)' : '(MoMo)' }}</label>
                    <input name="phone" class="form-control mb-2" placeholder="07xx" value="{{ old('phone', $invoice->client?->phone ?? $invoice->client_contact) }}" @if($yoEnabled) required @endif>
                </div>
                <div id="manual-fields" class="d-none">
                    <label class="form-label">Reference / transaction ID</label>
                    <input name="reference" class="form-control mb-3" placeholder="Optional">
                </div>
                <button class="btn btn-primary w-100" id="pay-btn">{{ $yoEnabled ? 'Pay with Mobile Money' : 'Confirm payment' }}</button>
                @if($yoEnabled)
                    <p class="small text-muted mt-2 mb-0">Yo Uganda will send a prompt to your phone. Approve it, then wait on the next screen. Cash, bank, and card still record immediately if you have a proof of payment.</p>
                @else
                    <p class="small text-muted mt-2 mb-0">Mobile Money PIN collection starts after Yo Uganda API details are saved in Settings. Cash and bank can still be confirmed here.</p>
                @endif
            </form>
        @else
            <div class="alert alert-success mb-0">This invoice is paid in full.</div>
        @endif
    </div>
</div>
<script>
(function () {
    const method = document.getElementById('pay-method');
    const momo = document.getElementById('momo-fields');
    const manual = document.getElementById('manual-fields');
    const btn = document.getElementById('pay-btn');
    const yo = {{ $yoEnabled ? 'true' : 'false' }};
    function sync() {
        const momoPay = method.value === 'mtn_momo' || method.value === 'airtel_money';
        momo.classList.toggle('d-none', !momoPay);
        manual.classList.toggle('d-none', momoPay);
        momo.querySelector('input').required = momoPay && yo;
        if (btn) btn.textContent = (yo && momoPay) ? 'Pay with Mobile Money' : 'Confirm payment';
    }
    method.addEventListener('change', sync);
    sync();
})();
</script>
@endsection
