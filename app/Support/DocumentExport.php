<?php

namespace App\Support;

use App\Models\CashBookEntry;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\JcTable;

class DocumentExport
{
    public static function invoicePdf(Invoice $invoice)
    {
        $invoice->load(['items', 'client', 'company']);
        $company = $invoice->company ?? auth()->user()->company;

        return Pdf::loadView('documents.invoice', compact('invoice', 'company'))
            ->setPaper('a4')
            ->setOptions(self::pdfOptions(), true);
    }

    public static function quotationPdf(Quotation $quotation)
    {
        $quotation->load(['items', 'client', 'company']);
        $company = $quotation->company ?? auth()->user()->company;

        return Pdf::loadView('documents.quotation', compact('quotation', 'company'))
            ->setPaper('a4')
            ->setOptions(self::pdfOptions(), true);
    }

    public static function receiptPdf(Receipt $receipt)
    {
        $receipt->loadMissing('company');
        $company = $receipt->company ?? auth()->user()->company;

        return Pdf::loadView('documents.receipt', compact('receipt', 'company'))
            ->setPaper('a4', 'portrait')
            ->setOptions(self::pdfOptions(), true);
    }

    public static function cashbookPdf(CashBookEntry $entry)
    {
        $entry->loadMissing('company');
        $company = $entry->company ?? auth()->user()->company;

        return Pdf::loadView('documents.cashbook', compact('entry', 'company'))
            ->setPaper('a4', 'landscape')
            ->setOptions(self::pdfOptions(), true);
    }

    public static function ledgerPdf(Collection $pages, Company $company, string $title = 'LEDGER')
    {
        return Pdf::loadView('documents.ledger', compact('pages', 'company', 'title'))
            ->setPaper('a4', 'landscape')
            ->setOptions(self::pdfOptions(), true);
    }

    public static function payslipPdf(\App\Models\PayrollItem $item, Company $company)
    {
        return Pdf::loadView('documents.payslip', compact('item', 'company'))
            ->setPaper('a4', 'portrait')
            ->setOptions(self::pdfOptions(), true);
    }

    protected static function pdfOptions(): array
    {
        return [
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ];
    }

    public static function sendPdf($pdf, string $filename, bool $inline = false)
    {
        $response = $inline ? $pdf->stream($filename) : $pdf->download($filename);
        if ($inline) {
            $response->headers->set('Cache-Control', 'private, no-store, no-cache, must-revalidate');
        }

        return $response;
    }

    public static function receiptDocx(Receipt $receipt): string
    {
        $receipt->loadMissing('company');
        $company = $receipt->company ?? auth()->user()->company;
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $logoPath = $company->logoFilesystemPath();

        $headerTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $headerTable->addRow();
        $logoCell = $headerTable->addCell(5500, ['valign' => 'top']);
        if ($logoPath) {
            $logoCell->addImage($logoPath, ['width' => 160, 'height' => 65]);
        } else {
            $logoCell->addText($company->displayNameUpper(), ['bold' => true, 'size' => 16]);
        }
        $logoCell->addText(strtoupper($company->taglineText()), ['bold' => true, 'size' => 8, 'name' => 'Arial']);

        $contactCell = $headerTable->addCell(4000, [
            'valign' => 'top',
            'borderLeftSize' => 6,
            'borderLeftColor' => '333333',
        ]);
        $contactStyle = ['size' => 8, 'name' => 'Arial'];
        $contactParaRight = ['alignment' => Jc::END];
        $contactCell->addText($company->phoneLine(), $contactStyle, $contactParaRight);
        foreach (preg_split("/\r\n|\n|\r/", $company->addressText()) as $line) {
            $contactCell->addText($line, $contactStyle, $contactParaRight);
        }
        foreach (preg_split("/\r\n|\n|\r/", $company->emailLines()) as $line) {
            $contactCell->addText($line, $contactStyle, $contactParaRight);
        }

        $servicesStyle = ['bold' => true, 'italic' => true, 'color' => '1f5fa8', 'size' => 10, 'name' => 'Arial'];
        foreach (preg_split("/\r\n|\n|\r/", $company->servicesText()) as $line) {
            $section->addText($line, $servicesStyle, ['alignment' => Jc::CENTER]);
        }

        $section->addText('', [], ['spaceAfter' => 40]);
        $barTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 0]);
        $barTable->addRow(60);
        $barTable->addCell(9500, ['bgColor' => '111111']);
        $section->addTextBreak(1);

        $topRow = $section->addTable(['borderSize' => 0, 'cellMargin' => 60, 'alignment' => JcTable::CENTER]);
        $topRow->addRow();
        $noCell = $topRow->addCell(3000, ['borderSize' => 12, 'borderColor' => '111111', 'valign' => 'center']);
        $noCell->addText('No.   '.$receipt->shortNumber(), ['bold' => true, 'color' => 'c0392b', 'italic' => true, 'size' => 13]);
        $pillCell = $topRow->addCell(3000, ['valign' => 'center']);
        $pillCell->addText('RECEIPT', ['bold' => true, 'color' => 'FFFFFF', 'size' => 13, 'name' => 'Arial'], [
            'alignment' => Jc::CENTER, 'shading' => ['fill' => '111111'],
        ]);
        $dateCell = $topRow->addCell(3000, ['borderSize' => 12, 'borderColor' => '111111', 'valign' => 'center']);
        $dateCell->addText('Date:  '.$receipt->issued_date?->format('d M Y'), ['size' => 12]);
        $section->addTextBreak(1);

        $fieldValue = ['size' => 12, 'underline' => 'single'];
        $section->addText('Received with thanks from:  '.$receipt->client_name, $fieldValue);
        $section->addTextBreak(1);
        $section->addText('Telephone:  '.$receipt->client_contact, $fieldValue);
        $section->addTextBreak(1);
        $section->addText('The amount of Shillings Shs/USD:  '.money_text($receipt->amount, $company), $fieldValue);
        $section->addTextBreak(1);
        $section->addText('Being payment of:  '.$receipt->description, $fieldValue);
        $section->addTextBreak(1);
        $section->addText('Cash/Cheque No.:  '.$receipt->reference_no, $fieldValue);
        $section->addTextBreak(2);

        $bottomRow = $section->addTable(['borderSize' => 0, 'cellMargin' => 60]);
        $bottomRow->addRow();
        $amountCell = $bottomRow->addCell(3200, ['borderSize' => 12, 'borderColor' => '111111', 'valign' => 'center']);
        $amountCell->addText(money_text($receipt->amount, $company), ['bold' => true, 'color' => 'c0392b', 'size' => 13], ['alignment' => Jc::CENTER]);
        $bottomRow->addCell(3200)->addText('');
        $sigCell = $bottomRow->addCell(3200, ['valign' => 'bottom']);
        $sigCell->addText('Served by: '.$receipt->servedByName(), ['size' => 11]);
        $section->addTextBreak(1);
        $section->addText('With thanks', ['italic' => true, 'size' => 12], ['alignment' => Jc::CENTER]);
        $section->addTextBreak(1);
        $section->addText('for; '.$company->displayNameUpper(), ['bold' => true, 'size' => 9, 'name' => 'Arial'], ['alignment' => Jc::END]);

        $path = storage_path('app/tmp_'.$receipt->number.'.docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }

    public static function invoiceDocx(Invoice $invoice): string
    {
        $invoice->load(['items', 'client']);
        $company = $invoice->company ?? auth()->user()->company;
        $phpWord = new PhpWord;
        $section = $phpWord->addSection();
        $logoPath = $company->logoFilesystemPath();
        if ($logoPath) {
            $section->addImage($logoPath, ['height' => 45, 'alignment' => Jc::CENTER]);
        }
        $section->addText($company->name, ['bold' => true, 'size' => 18, 'color' => '0d6efd'], ['alignment' => Jc::CENTER]);
        $section->addText(($company->address ?: '').'  '.$company->email.' | '.$company->phone, ['size' => 9, 'color' => '666666'], ['alignment' => Jc::CENTER]);
        $section->addTextBreak();
        $section->addText('INVOICE  '.$invoice->invoice_number, ['bold' => true, 'size' => 16]);
        $section->addText('Status: '.$invoice->status);
        $section->addTextBreak();
        $section->addText('FROM: '.$company->name, ['bold' => true]);
        $section->addText($company->address);
        $section->addText('BILL TO: '.$invoice->displayClient(), ['bold' => true]);
        if ($invoice->client?->company) {
            $section->addText($invoice->client->company);
        }
        $section->addText('Date: '.$invoice->date?->format('M d, Y').'    Due: '.$invoice->due_date?->format('M d, Y'));
        $section->addTextBreak();
        foreach ($invoice->items as $item) {
            $section->addText($item->item_name.'  x '.$item->qty.'    '.money_text($item->total, $company));
        }
        $section->addTextBreak();
        $section->addText('Subtotal: '.money_text($invoice->subtotal, $company));
        $section->addText('Tax: '.money_text($invoice->tax, $company));
        if ((float) $invoice->discount > 0) {
            $section->addText('Discount: -'.money_text($invoice->discount, $company));
        }
        $section->addText('AMOUNT DUE: '.money_text($invoice->total, $company), ['bold' => true, 'size' => 13]);
        if ($invoice->notes) {
            $section->addTextBreak();
            $section->addText('Notes: '.$invoice->notes);
        }
        $section->addTextBreak();
        $section->addText('Thank you for your business!', ['italic' => true], ['alignment' => Jc::CENTER]);

        $path = storage_path('app/tmp_'.$invoice->invoice_number.'.docx');
        IOFactory::createWriter($phpWord, 'Word2007')->save($path);

        return $path;
    }
}
