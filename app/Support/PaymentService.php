<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Support\Audit;
use App\Support\CashBookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public static function record(Invoice $invoice, array $data): Payment
    {
        $invoice->refresh();
        if (! $invoice->isOpen()) {
            throw ValidationException::withMessages(['invoice' => 'This invoice is not open for payment.']);
        }

        $amount = round((float) $data['amount'], 2);
        $outstanding = round($invoice->outstanding(), 2);
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }
        if ($amount > $outstanding + 0.009) {
            throw ValidationException::withMessages(['amount' => 'Amount exceeds the outstanding balance of '.money_text($outstanding, $invoice->company).'.']);
        }

        if (! empty($data['provider_ref'])) {
            $existing = Payment::withoutGlobalScopes()
                ->where('invoice_id', $invoice->id)
                ->where('provider_ref', $data['provider_ref'])
                ->where('status', 'paid')
                ->first();
            if ($existing) {
                return $existing->load('receipt');
            }

            $pending = Payment::withoutGlobalScopes()
                ->where('invoice_id', $invoice->id)
                ->where('provider_ref', $data['provider_ref'])
                ->where('status', 'pending')
                ->first();
            if ($pending) {
                return self::complete($pending, $data);
            }
        }

        return DB::transaction(function () use ($invoice, $data, $amount, $outstanding) {
            $payment = self::makePaymentRow($invoice, $data, $amount, 'paid');

            return self::postPaid($invoice, $payment, $data, $amount, $outstanding);
        });
    }

    public static function initiateYo(Invoice $invoice, array $data): Payment
    {
        $invoice->loadMissing('company');
        $yo = YoPayments::forCompany($invoice->company);
        if (! $yo) {
            throw ValidationException::withMessages(['method' => 'Yo Uganda is not configured. Save API username and password in Settings.']);
        }

        $invoice->refresh();
        if (! $invoice->isOpen()) {
            throw ValidationException::withMessages(['invoice' => 'This invoice is not open for payment.']);
        }

        $amount = round((float) $data['amount'], 2);
        $outstanding = round($invoice->outstanding(), 2);
        if ($amount <= 0 || $amount > $outstanding + 0.009) {
            throw ValidationException::withMessages(['amount' => 'Enter an amount up to '.money_text($outstanding, $invoice->company).'.']);
        }

        $msisdn = YoPayments::normalizeMsisdn((string) ($data['phone'] ?? ''));
        if (strlen($msisdn) < 12) {
            throw ValidationException::withMessages(['phone' => 'Enter a valid MTN or Airtel number (07xx or 2567xx).']);
        }

        $method = $data['method'] ?? YoPayments::guessMethod($msisdn);
        if (! in_array($method, ['mtn_momo', 'airtel_money'], true)) {
            $method = YoPayments::guessMethod($msisdn);
        }

        $payment = DB::transaction(function () use ($invoice, $data, $amount, $msisdn, $method) {
            return self::makePaymentRow($invoice, [
                'method' => $method,
                'phone' => $msisdn,
                'provider' => 'yo',
                'reference' => $data['reference'] ?? null,
                'meta' => [
                    'msisdn' => $msisdn,
                    'narrative' => 'Invoice '.$invoice->invoice_number,
                ],
            ], $amount, 'pending');
        });

        $ipn = url('/pay/webhook/yo');
        $fail = url('/pay/webhook/yo-fail');
        $options = [
            'external_reference' => $payment->number,
            'provider_reference_text' => $invoice->invoice_number,
            'non_blocking' => true,
        ];
        $host = parse_url($ipn, PHP_URL_HOST);
        $publicHost = $host && ! in_array($host, ['localhost', '127.0.0.1', '::1'], true);
        if ($publicHost) {
            $options['instant_notification_url'] = $ipn;
            $options['failure_notification_url'] = $fail;
        }

        $response = $yo->deposit($msisdn, $amount, 'Invoice '.$invoice->invoice_number, $options);

        $status = strtoupper((string) ($response['TransactionStatus'] ?? $response['Status'] ?? ''));
        $payment->provider_ref = $response['TransactionReference'] ?? $payment->provider_ref;
        $payment->reference = $response['MNOTransactionReferenceId'] ?? $payment->reference;
        $meta = $payment->meta ?? [];
        $meta['yo_request'] = [
            'Status' => $response['Status'] ?? null,
            'StatusCode' => $response['StatusCode'] ?? null,
            'StatusMessage' => $response['StatusMessage'] ?? null,
            'TransactionStatus' => $response['TransactionStatus'] ?? null,
        ];
        $payment->meta = $meta;

        if (strtoupper((string) ($response['Status'] ?? '')) !== 'OK') {
            $payment->status = 'failed';
            $payment->save();
            $message = $response['ErrorMessage'] ?? $response['StatusMessage'] ?? 'Yo Payments rejected the request.';
            throw ValidationException::withMessages(['phone' => $message]);
        }

        $payment->save();

        if (in_array($status, ['SUCCEEDED', 'SUCCESS', 'OK'], true)) {
            return self::complete($payment, [
                'phone' => $msisdn,
                'method' => $method,
                'reference' => $payment->reference,
                'provider_ref' => $payment->provider_ref,
                'provider' => 'yo',
            ]);
        }

        if (in_array($status, ['FAILED', 'FAILURE', 'ERROR'], true)) {
            $payment->status = 'failed';
            $payment->save();
            throw ValidationException::withMessages(['phone' => $response['StatusMessage'] ?? 'The payment request failed.']);
        }

        return $payment;
    }

    public static function complete(Payment $payment, array $data = []): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'paid') {
                return $payment->load('receipt');
            }
            if ($payment->status === 'failed') {
                throw ValidationException::withMessages(['invoice' => 'This payment request already failed. Start a new one.']);
            }

            $invoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->invoice_id);
            $invoice->loadMissing('company');
            if (! $invoice->isOpen()) {
                throw ValidationException::withMessages(['invoice' => 'This invoice is not open for payment.']);
            }

            $amount = round((float) ($data['amount'] ?? $payment->amount), 2);
            $outstanding = round($invoice->outstanding(), 2);
            if ($amount > $outstanding + 0.009) {
                $amount = $outstanding;
            }
            if ($amount <= 0) {
                $payment->status = 'failed';
                $payment->save();
                throw ValidationException::withMessages(['amount' => 'Nothing left to collect on this invoice.']);
            }

            $payment->amount = $amount;
            $payment->method = $data['method'] ?? $payment->method;
            $payment->phone = $data['phone'] ?? $payment->phone;
            $payment->reference = $data['reference'] ?? $payment->reference;
            $payment->provider_ref = $data['provider_ref'] ?? $payment->provider_ref;
            $payment->provider = $data['provider'] ?? $payment->provider;
            $payment->save();

            return self::postPaid($invoice, $payment, $data, $amount, $outstanding);
        });
    }

    public static function fail(Payment $payment, string $reason = ''): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            $payment = Payment::withoutGlobalScopes()->lockForUpdate()->findOrFail($payment->id);
            if ($payment->status === 'paid') {
                return $payment;
            }
            $payment->status = 'failed';
            $meta = $payment->meta ?? [];
            $meta['failure'] = $reason ?: 'Failed';
            $payment->meta = $meta;
            $payment->save();

            return $payment;
        });
    }

    public static function settleFromYo(Payment $payment): Payment
    {
        if ($payment->status !== 'pending' || $payment->provider !== 'yo') {
            return $payment;
        }

        $payment->loadMissing('invoice.company');
        $yo = YoPayments::forCompany($payment->invoice->company);
        if (! $yo) {
            return $payment;
        }

        $response = $yo->checkStatus($payment->provider_ref, $payment->number);
        $meta = $payment->meta ?? [];
        $meta['yo_status'] = [
            'Status' => $response['Status'] ?? null,
            'TransactionStatus' => $response['TransactionStatus'] ?? null,
            'checked_at' => now()->toIso8601String(),
        ];
        $payment->meta = $meta;
        $payment->save();

        $txn = strtoupper((string) ($response['TransactionStatus'] ?? ''));
        if (in_array($txn, ['SUCCEEDED', 'SUCCESS'], true)) {
            return self::complete($payment, [
                'amount' => $response['Amount'] ?? $payment->amount,
                'phone' => $payment->phone,
                'method' => $payment->method,
                'reference' => $response['MNOTransactionReferenceId'] ?? $payment->reference,
                'provider_ref' => $response['TransactionReference'] ?? $payment->provider_ref,
                'provider' => 'yo',
            ]);
        }
        if (in_array($txn, ['FAILED', 'FAILURE'], true)) {
            return self::fail($payment, $response['StatusMessage'] ?? $response['ErrorMessage'] ?? 'Yo reported a failed collection.');
        }
        if ($payment->created_at?->lt(now()->subMinutes(45))) {
            return self::fail($payment, 'Timed out waiting for Mobile Money approval.');
        }

        return $payment;
    }

    public static function handleYoIpn(array $payload, bool $failed = false): ?Payment
    {
        $external = (string) ($payload['external_ref'] ?? $payload['failed_transaction_reference'] ?? '');
        $networkRef = (string) ($payload['network_ref'] ?? '');
        $msisdn = (string) ($payload['msisdn'] ?? '');

        if ($external === '' && $networkRef === '') {
            Log::info('Yo IPN missing references');

            return null;
        }

        $payment = Payment::withoutGlobalScopes()
            ->where('provider', 'yo')
            ->where(function ($q) use ($external, $networkRef) {
                if ($external !== '') {
                    $q->orWhere('number', $external)->orWhere('provider_ref', $external);
                }
                if ($networkRef !== '') {
                    $q->orWhere('provider_ref', $networkRef)->orWhere('reference', $networkRef);
                }
            })
            ->latest('id')
            ->first();

        if (! $payment) {
            Log::info('Yo IPN for unknown payment', ['external_ref' => $external]);

            return null;
        }

        try {
            $payment->loadMissing('invoice.company');
            $yo = YoPayments::forCompany($payment->invoice->company);
            $verified = $yo?->verifyIpn($payload) ?? false;
            $meta = $payment->meta ?? [];
            $meta['ipn'] = [
                'verified' => $verified,
                'external_ref' => $external,
                'network_ref' => $networkRef,
                'msisdn' => $msisdn,
                'amount' => $payload['amount'] ?? null,
            ];
            $payment->meta = $meta;
            $payment->save();

            if ($failed) {
                return $yo
                    ? self::settleFromYo($payment)
                    : self::fail($payment, 'Yo failure notification');
            }

            if ($yo) {
                return self::settleFromYo($payment);
            }

            if (! $verified) {
                Log::warning('Yo IPN skipped: not verified and API not configured', ['payment' => $payment->number]);

                return $payment;
            }

            return self::complete($payment, [
                'amount' => $payload['amount'] ?? $payment->amount,
                'phone' => YoPayments::normalizeMsisdn($msisdn) ?: $payment->phone,
                'method' => $msisdn ? YoPayments::guessMethod($msisdn) : $payment->method,
                'reference' => $networkRef ?: $payment->reference,
                'provider_ref' => $payment->provider_ref ?: $networkRef,
                'provider' => 'yo',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Yo IPN handling failed', ['payment' => $payment->number, 'error' => $e->getMessage()]);

            return $payment;
        }
    }

    protected static function makePaymentRow(Invoice $invoice, array $data, float $amount, string $status): Payment
    {
        $companyId = (int) $invoice->company_id;

        return Payment::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'invoice_id' => $invoice->id,
            'receipt_id' => null,
            'number' => DocumentNumber::next('PAY', 'payments', 'number', $companyId),
            'amount' => $amount,
            'method' => $data['method'] ?? 'mtn_momo',
            'phone' => $data['phone'] ?? null,
            'reference' => $data['reference'] ?? null,
            'status' => $status,
            'provider' => $data['provider'] ?? 'manual',
            'provider_ref' => $data['provider_ref'] ?? null,
            'paid_at' => $status === 'paid' ? now() : null,
            'meta' => $data['meta'] ?? null,
            'created_by' => auth()->id(),
        ]);
    }

    protected static function postPaid(Invoice $invoice, Payment $payment, array $data, float $amount, float $outstanding): Payment
    {
        $companyId = (int) $invoice->company_id;
        $method = $payment->method ?: ($data['method'] ?? 'mtn_momo');
        $balance = max(0, $outstanding - $amount);

        $receipt = Receipt::withoutGlobalScopes()->create([
            'company_id' => $companyId,
            'invoice_id' => $invoice->id,
            'number' => DocumentNumber::next('RCT', 'receipts', 'number', $companyId),
            'client_name' => $invoice->displayClient(),
            'client_contact' => $data['phone'] ?? $payment->phone ?? $invoice->client_contact ?? $invoice->client?->phone,
            'description' => 'Payment for '.$invoice->invoice_number,
            'amount' => $amount,
            'payment_method' => $method,
            'issued_date' => now()->toDateString(),
            'reference_no' => $data['reference'] ?? $payment->reference,
            'balance' => $balance > 0 ? (string) $balance : '0',
            'created_by' => auth()->id(),
        ]);

        CashBookService::record([
            'company_id' => $companyId,
            'entry_date' => now()->toDateString(),
            'description' => 'Receipt '.$receipt->number.' — '.$receipt->client_name,
            'type' => 'debit',
            'amount' => $amount,
            'payment_method' => $method,
            'invoice_id' => $invoice->id,
        ]);

        LedgerService::postReceipt($receipt);

        $payment->receipt_id = $receipt->id;
        $payment->status = 'paid';
        $payment->paid_at = now();
        $payment->amount = $amount;
        $payment->save();

        $paid = (float) $invoice->amount_paid + $amount;
        $invoice->amount_paid = $paid;
        $invoice->status = $paid + 0.009 >= (float) $invoice->total ? 'Paid' : 'Partially Paid';
        $invoice->save();

        Audit::log('Pay', 'Invoice', $invoice->id, $invoice->invoice_number.' · '.money_text($amount, $invoice->company), $amount);

        return $payment->load('receipt');
    }
}
