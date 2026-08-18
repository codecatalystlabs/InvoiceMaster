<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Support\PaymentService;
use App\Support\YoPayments;
use Illuminate\Http\Request;

class PublicPaymentController extends Controller
{
    public function show(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()->where('pay_token', $token)->firstOrFail();
        $invoice->load(['items', 'client', 'company']);
        $yoEnabled = YoPayments::enabled($invoice->company);
        $pending = $this->pendingYo($invoice);

        return view('pay.show', compact('invoice', 'yoEnabled', 'pending'));
    }

    public function store(Request $request, string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()->where('pay_token', $token)->firstOrFail();
        $invoice->loadMissing('company');
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:mtn_momo,airtel_money,bank,cash,card',
            'phone' => 'nullable|string|max:30',
            'reference' => 'nullable|string|max:80',
        ]);

        $useYo = YoPayments::enabled($invoice->company)
            && in_array($data['method'], ['mtn_momo', 'airtel_money'], true);

        if ($useYo) {
            $request->validate(['phone' => 'required|string|max:30']);
            $payment = PaymentService::initiateYo($invoice, $data);
            if ($payment->status === 'paid') {
                return redirect()->route('pay.thanks', $token)
                    ->with('success', 'Payment received. Receipt '.$payment->receipt?->number.' issued.');
            }

            return redirect()->route('pay.wait', $token)
                ->with('success', 'Approve the prompt on '.$payment->phone.'. We will issue a receipt as soon as Yo confirms.');
        }

        $payment = PaymentService::record($invoice, $data + ['provider' => 'manual']);

        return redirect()->route('pay.thanks', $token)->with('success', 'Payment received. Receipt '.$payment->receipt?->number.' issued.');
    }

    public function wait(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()->where('pay_token', $token)->firstOrFail();
        $invoice->load('company');
        $pending = $this->pendingYo($invoice);
        if (! $pending) {
            if (! $invoice->isOpen() || $invoice->outstanding() <= 0) {
                return redirect()->route('pay.thanks', $token);
            }

            return redirect()->route('pay.show', $token);
        }

        PaymentService::settleFromYo($pending);
        $pending->refresh();
        if ($pending->status === 'paid') {
            return redirect()->route('pay.thanks', $token)->with('success', 'Payment confirmed.');
        }
        if ($pending->status === 'failed') {
            return redirect()->route('pay.show', $token)->with('error', 'That Mobile Money request did not complete. Try again.');
        }

        return view('pay.wait', compact('invoice', 'pending'));
    }

    public function status(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()->where('pay_token', $token)->firstOrFail();
        $pending = $this->pendingYo($invoice);
        if ($pending) {
            $checkedAt = $pending->meta['yo_status']['checked_at'] ?? null;
            $stale = ! $checkedAt || \Illuminate\Support\Carbon::parse($checkedAt)->lt(now()->subSeconds(8));
            if ($stale) {
                PaymentService::settleFromYo($pending);
                $pending->refresh();
            }
        }

        $invoice->refresh();

        return response()->json([
            'status' => $pending?->status ?? ($invoice->outstanding() <= 0 ? 'paid' : 'open'),
            'outstanding' => $invoice->outstanding(),
            'paid' => (float) $invoice->amount_paid,
        ]);
    }

    public function thanks(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()->where('pay_token', $token)->firstOrFail();
        $invoice->load(['receipts', 'company']);

        return view('pay.thanks', compact('invoice'));
    }

    public function webhook(Request $request, string $provider)
    {
        $provider = strtolower($provider);
        if (in_array($provider, ['yo', 'yo-fail'], true)) {
            PaymentService::handleYoIpn($request->all(), $provider === 'yo-fail');

            return response('OK', 200);
        }

        $token = $request->input('token') ?: $request->input('pay_token');
        $invoice = Invoice::withoutGlobalScopes()->where('pay_token', $token)->first();
        if (! $invoice) {
            return response()->json(['ok' => false, 'error' => 'Invoice not found'], 404);
        }
        PaymentService::record($invoice, [
            'amount' => $request->input('amount', $invoice->outstanding()),
            'method' => $request->input('method', 'mtn_momo'),
            'phone' => $request->input('phone'),
            'reference' => $request->input('reference') ?: $request->input('txn_id'),
            'provider' => $provider,
            'provider_ref' => $request->input('txn_id'),
        ]);

        return response()->json(['ok' => true]);
    }

    protected function pendingYo(Invoice $invoice): ?Payment
    {
        return Payment::withoutGlobalScopes()
            ->where('invoice_id', $invoice->id)
            ->where('provider', 'yo')
            ->where('status', 'pending')
            ->latest('id')
            ->first();
    }
}
