<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\DocumentMailer;
use App\Support\NoticeService;
use Illuminate\Http\Request;

class ReceivableController extends Controller
{
    public function index()
    {
        $open = Invoice::with('client')
            ->whereNotIn('status', ['Paid', 'paid', 'Cancelled', 'cancelled'])
            ->orderBy('due_date')
            ->get()
            ->map(function (Invoice $inv) {
                $due = $inv->due_date;
                $days = $due ? (int) now()->startOfDay()->diffInDays($due, false) * -1 : 0;
                if ($due && $due->isFuture()) {
                    $days = - (int) now()->startOfDay()->diffInDays($due);
                }
                $bucket = 'Current';
                if ($due && $due->isPast()) {
                    $over = (int) $due->diffInDays(now());
                    $bucket = $over <= 30 ? '1-30' : ($over <= 60 ? '31-60' : ($over <= 90 ? '61-90' : '90+'));
                }

                return compact('inv') + ['days' => $days, 'bucket' => $bucket, 'outstanding' => $inv->outstanding()];
            });

        $totals = [
            'Current' => 0, '1-30' => 0, '31-60' => 0, '61-90' => 0, '90+' => 0,
        ];
        foreach ($open as $row) {
            $totals[$row['bucket']] += $row['outstanding'];
        }

        return view('receivables.index', compact('open', 'totals'));
    }

    public function remind(Request $request, Invoice $invoice)
    {
        $to = $invoice->recipientEmail();
        if (! $to) {
            return back()->with('error', 'This invoice has no email address.');
        }
        $message = 'This is a reminder that invoice '.$invoice->invoice_number.' of '.money_text($invoice->outstanding()).' is outstanding. Pay online: '.$invoice->payUrl();
        $result = DocumentMailer::sendInvoice($invoice, $to, $message);
        NoticeService::invoiceDue($invoice);
        if (! ($result['success'] ?? false)) {
            return back()->with('error', 'Reminder email failed: '.($result['error'] ?? 'unknown'));
        }

        return back()->with('success', 'Reminder sent to '.$to);
    }
}
