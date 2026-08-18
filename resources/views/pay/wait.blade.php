@extends('layouts.public')
@section('title', 'Approve payment')
@section('content')
<div class="card">
    <div class="card-body text-center">
        <h1 class="h4">Approve on your phone</h1>
        <p class="mb-1">Invoice {{ $invoice->invoice_number }} · {{ money($pending->amount, $invoice->company) }}</p>
        <p>Dial prompt sent to <strong>{{ $pending->phone }}</strong> via Yo Uganda.</p>
        <p class="text-muted">Enter your Mobile Money PIN. This page updates when Yo confirms the collection.</p>
        <div class="spinner-border text-primary my-3" role="status"><span class="visually-hidden">Waiting</span></div>
        <p class="small mb-3" id="wait-note">Waiting for confirmation…</p>
        <a class="btn btn-outline-secondary" href="{{ route('pay.show', $invoice->pay_token) }}">Cancel and go back</a>
    </div>
</div>
<script>
(function () {
    const statusUrl = @json(route('pay.status', $invoice->pay_token));
    const thanksUrl = @json(route('pay.thanks', $invoice->pay_token));
    const showUrl = @json(route('pay.show', $invoice->pay_token));
    async function poll() {
        try {
            const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (data.status === 'paid') {
                window.location.href = thanksUrl;
                return;
            }
            if (data.status === 'failed') {
                window.location.href = showUrl;
                return;
            }
        } catch (e) {}
        setTimeout(poll, 4000);
    }
    setTimeout(poll, 3000);
})();
</script>
@endsection
