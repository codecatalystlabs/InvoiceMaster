<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use App\Support\Audit;
use App\Support\CashBookService;
use App\Support\LedgerService;
use App\Support\DocumentExport;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $receipts = Receipt::when($q, fn ($query) => $query->where('client_name', 'like', "%$q%")->orWhere('number', 'like', "%$q%"))
            ->latest('issued_date')
            ->paginate(20)
            ->withQueryString();

        $total = (float) Receipt::sum('amount');
        $count = Receipt::count();
        $average = $count ? $total / $count : 0;

        return view('receipts.index', compact('receipts', 'q', 'total', 'count', 'average'));
    }

    public function create()
    {
        return view('receipts.form', ['receipt' => new Receipt(['issued_date' => now(), 'payment_method' => 'cash'])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['number'] = DocumentNumber::next('RCT', 'receipts', 'number', auth()->user()->company_id);
        $receipt = Receipt::create($data);

        CashBookService::record([
            'entry_date' => $receipt->issued_date,
            'description' => 'Receipt '.$receipt->number.' — '.$receipt->client_name,
            'type' => 'debit',
            'amount' => $receipt->amount,
            'payment_method' => $receipt->payment_method,
        ]);
        LedgerService::postReceipt($receipt);

        Audit::log('Create', 'Receipt', $receipt->id, $receipt->number);

        return redirect()->route('receipts.show', $receipt)->with('success', 'Receipt recorded.');
    }

    public function show(Receipt $receipt)
    {
        $receipt->loadMissing('company');

        return view('receipts.show', compact('receipt'));
    }

    public function edit(Receipt $receipt)
    {
        return view('receipts.form', compact('receipt'));
    }

    public function update(Request $request, Receipt $receipt)
    {
        $receipt->update($this->validated($request));
        LedgerService::postReceipt($receipt);
        Audit::log('Update', 'Receipt', $receipt->id, $receipt->number);

        return redirect()->route('receipts.show', $receipt)->with('success', 'Receipt updated.');
    }

    public function destroy(Receipt $receipt)
    {
        LedgerService::forget('Receipt', $receipt->id);
        Audit::log('Delete', 'Receipt', $receipt->id, $receipt->number);
        $receipt->delete();

        return redirect()->route('receipts.index')->with('success', 'Receipt deleted.');
    }

    public function pdf(Request $request, Receipt $receipt)
    {
        return DocumentExport::sendPdf(
            DocumentExport::receiptPdf($receipt),
            $receipt->number.'.pdf',
            $request->boolean('inline')
        );
    }

    public function docx(Receipt $receipt)
    {
        $path = DocumentExport::receiptDocx($receipt);

        return response()->download($path, $receipt->number.'.docx')->deleteFileAfterSend(true);
    }

    public function emailForm(Receipt $receipt)
    {
        $receipt->loadMissing('invoice.client');

        return view('emails.send', [
            'heading' => 'Email receipt '.$receipt->number,
            'action' => route('receipts.email.send', $receipt),
            'to' => $receipt->recipientEmail(),
            'defaultMessage' => 'Please find attached receipt '.$receipt->number.' for '.money_text($receipt->amount).'.',
            'back' => route('receipts.show', $receipt),
        ]);
    }

    public function sendEmail(Request $request, Receipt $receipt)
    {
        $data = $request->validate([
            'to' => 'required|email',
            'message' => 'nullable|string',
        ]);
        $result = \App\Support\DocumentMailer::sendReceipt($receipt, $data['to'], $data['message'] ?? null);
        if (! $result['success']) {
            return back()->withInput()->with('error', 'Email failed: '.$result['error']);
        }

        return redirect()->route('receipts.show', $receipt)->with('success', 'Receipt emailed to '.$data['to']);
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'client_name' => 'required|string|max:150',
            'client_contact' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'issued_date' => 'required|date',
            'reference_no' => 'nullable|string',
            'balance' => 'nullable|string',
            'invoice_id' => 'nullable|exists:invoices,id',
        ]);
    }
}
