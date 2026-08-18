<?php

namespace App\Support;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\NoticeLog;
use Illuminate\Support\Facades\Http;
use Throwable;

class NoticeService
{
    public static function whatsapp(Company $company, string $to, string $body, ?string $type = null, ?int $id = null): NoticeLog
    {
        $log = NoticeLog::create([
            'company_id' => $company->id,
            'channel' => 'whatsapp',
            'to' => $to,
            'body' => $body,
            'status' => 'queued',
            'reference_type' => $type,
            'reference_id' => $id,
        ]);

        $token = $company->setting('whatsapp_token');
        $phoneId = $company->setting('whatsapp_phone_id');
        if (! $token || ! $phoneId) {
            $log->update(['status' => 'logged', 'error_message' => 'WhatsApp is not configured. Message stored for sending later.']);

            return $log;
        }

        try {
            $response = Http::withToken($token)
                ->post('https://graph.facebook.com/v20.0/'.$phoneId.'/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => preg_replace('/\D/', '', $to),
                    'type' => 'text',
                    'text' => ['body' => $body],
                ]);
            $log->update([
                'status' => $response->successful() ? 'sent' : 'failed',
                'error_message' => $response->successful() ? null : $response->body(),
            ]);
        } catch (Throwable $e) {
            $log->update(['status' => 'failed', 'error_message' => $e->getMessage()]);
        }

        return $log;
    }

    public static function invoiceDue(Invoice $invoice): ?NoticeLog
    {
        $invoice->loadMissing(['client', 'company']);
        $to = $invoice->client?->phone ?: $invoice->client_contact;
        if (! $to) {
            return null;
        }
        $body = $invoice->company->name.' invoice '.$invoice->invoice_number.' of '.money_text($invoice->outstanding(), $invoice->company).' is due. Pay: '.$invoice->payUrl();

        return self::whatsapp($invoice->company, $to, $body, 'Invoice', $invoice->id);
    }
}
