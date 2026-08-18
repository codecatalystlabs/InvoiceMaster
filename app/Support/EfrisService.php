<?php

namespace App\Support;

use App\Models\EfrisSubmission;
use App\Models\Invoice;

class EfrisService
{
    public static function queue(Invoice $invoice): EfrisSubmission
    {
        $invoice->loadMissing(['items', 'client', 'company']);
        $payload = [
            'invoice_number' => $invoice->invoice_number,
            'date' => $invoice->date?->toDateString(),
            'buyer' => $invoice->displayClient(),
            'tin' => $invoice->company?->setting('ura_tin'),
            'items' => $invoice->items->map(fn ($i) => [
                'name' => $i->item_name,
                'qty' => $i->qty,
                'unit_price' => $i->unit_price,
                'total' => $i->total,
            ])->all(),
            'tax' => $invoice->tax,
            'total' => $invoice->total,
        ];

        $row = EfrisSubmission::firstOrNew([
            'company_id' => $invoice->company_id,
            'invoice_id' => $invoice->id,
        ]);
        $row->status = 'queued';
        $row->request_payload = json_encode($payload);
        $row->error_message = $invoice->company?->setting('efris_device_no')
            ? null
            : 'URA EFRIS device is not configured. Submission is queued until credentials are saved in Settings.';
        $row->save();

        return $row;
    }
}
