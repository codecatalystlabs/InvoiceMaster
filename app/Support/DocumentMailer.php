<?php

namespace App\Support;

use App\Mail\DocumentMail;
use App\Models\Company;
use App\Models\EmailMessage;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class DocumentMailer
{
    public static function sendInvoice(Invoice $invoice, string $to, ?string $message = null): array
    {
        $invoice->loadMissing(['items', 'client', 'company']);
        $company = $invoice->company ?? auth()->user()->company;
        $filename = $invoice->invoice_number.'.pdf';
        $pdf = DocumentExport::invoicePdf($invoice);
        $path = self::savePdf($pdf, $filename);

        $intro = $message ?: 'Please find attached invoice '.$invoice->invoice_number.'.';
        if ($invoice->isOpen()) {
            $intro .= "\n\nPay online: ".$invoice->payUrl();
        }

        return self::dispatch(
            company: $company,
            to: $to,
            subject: 'Invoice '.$invoice->invoice_number.' from '.$company->name,
            intro: $intro,
            docLabel: 'Invoice',
            docNumber: $invoice->invoice_number,
            amountLabel: money_text($invoice->total),
            pdfPath: $path,
            pdfName: $filename,
            referenceType: 'invoice',
            referenceId: $invoice->id,
        );
    }

    public static function sendQuotation(Quotation $quotation, string $to, ?string $message = null): array
    {
        $quotation->loadMissing(['items', 'client', 'company']);
        $company = $quotation->company ?? auth()->user()->company;
        $filename = $quotation->quotation_number.'.pdf';
        $pdf = DocumentExport::quotationPdf($quotation);
        $path = self::savePdf($pdf, $filename);

        return self::dispatch(
            company: $company,
            to: $to,
            subject: 'Quotation '.$quotation->quotation_number.' from '.$company->name,
            intro: $message ?: 'Please find attached quotation '.$quotation->quotation_number.'.',
            docLabel: 'Quotation',
            docNumber: $quotation->quotation_number,
            amountLabel: money_text($quotation->total),
            pdfPath: $path,
            pdfName: $filename,
            referenceType: 'quotation',
            referenceId: $quotation->id,
        );
    }

    public static function sendReceipt(Receipt $receipt, string $to, ?string $message = null): array
    {
        $receipt->loadMissing('company');
        $company = $receipt->company ?? auth()->user()->company;
        $filename = $receipt->number.'.pdf';
        $pdf = DocumentExport::receiptPdf($receipt);
        $path = self::savePdf($pdf, $filename);

        return self::dispatch(
            company: $company,
            to: $to,
            subject: 'Receipt '.$receipt->number.' from '.$company->name,
            intro: $message ?: 'Please find attached receipt '.$receipt->number.'.',
            docLabel: 'Receipt',
            docNumber: $receipt->number,
            amountLabel: money_text($receipt->amount),
            pdfPath: $path,
            pdfName: $filename,
            referenceType: 'receipt',
            referenceId: $receipt->id,
        );
    }

    protected static function savePdf($pdf, string $filename): string
    {
        $dir = storage_path('app/mail');
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        $path = $dir.DIRECTORY_SEPARATOR.uniqid('', true).'_'.$safe;
        $pdf->save($path);

        return $path;
    }

    protected static function dispatch(
        Company $company,
        string $to,
        string $subject,
        string $intro,
        string $docLabel,
        string $docNumber,
        string $amountLabel,
        string $pdfPath,
        string $pdfName,
        string $referenceType,
        int $referenceId,
    ): array {
        $status = 'sent';
        $error = null;
        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';
        $messageId = Str::uuid().'@'.$host;

        try {
            Mail::to($to)->send(new DocumentMail(
                company: $company,
                emailSubject: $subject,
                intro: $intro,
                docLabel: $docLabel,
                docNumber: $docNumber,
                amountLabel: $amountLabel,
                pdfPath: $pdfPath,
                pdfName: $pdfName,
                messageId: $messageId,
            ));
        } catch (Throwable $e) {
            $status = 'failed';
            $error = $e->getMessage();
        }

        EmailMessage::create([
            'message_id' => $messageId,
            'from_email' => config('mail.from.address'),
            'from_name' => $company->name,
            'to_email' => $to,
            'subject' => $subject,
            'body_html' => $intro,
            'status' => $status,
            'error_message' => $error,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'direction' => 'outgoing',
            'sent_by' => auth()->id(),
            'sent_at' => now(),
        ]);

        Audit::log('Email', ucfirst($referenceType), $referenceId, $status.' to '.$to);

        return ['success' => $status === 'sent', 'error' => $error];
    }
}
