<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Support\Audit;
use App\Support\CashBookService;
use App\Support\DocumentExport;
use App\Support\DocumentNumber;
use App\Support\LedgerService;
use App\Support\LineTotals;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $invoices = Invoice::with('client')
            ->when($q, fn ($query) => $query->where(function ($w) use ($q) {
                $w->where('invoice_number', 'like', "%$q%")
                    ->orWhere('client_name', 'like', "%$q%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%$q%"));
            }))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'count' => Invoice::count(),
            'value' => (float) Invoice::sum('total'),
            'overdue' => Invoice::whereIn('status', ['Overdue', 'overdue'])->count(),
            'overdue_value' => (float) Invoice::whereIn('status', ['Overdue', 'overdue'])->sum('total'),
        ];

        return view('invoices.index', compact('invoices', 'q', 'status', 'summary'));
    }

    public function create()
    {
        return view('invoices.form', [
            'invoice' => new Invoice(['date' => now(), 'due_date' => now()->addDays(30), 'status' => 'Unpaid']),
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $invoice = $this->persist($request, new Invoice);
        Audit::log('Create', 'Invoice', $invoice->id, $invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['items', 'client', 'receipts']);

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        $invoice->load('items');

        return view('invoices.form', [
            'invoice' => $invoice,
            'clients' => Client::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        $this->persist($request, $invoice);
        Audit::log('Update', 'Invoice', $invoice->id, $invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice updated.');
    }

    public function destroy(Invoice $invoice)
    {
        LedgerService::forget('Invoice', $invoice->id);
        Audit::log('Delete', 'Invoice', $invoice->id, $invoice->invoice_number);
        $invoice->delete();

        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');
    }

    public function markPaid(Invoice $invoice)
    {
        \App\Support\PaymentService::record($invoice, [
            'amount' => $invoice->outstanding(),
            'method' => 'cash',
            'reference' => 'Marked paid',
            'provider' => 'manual',
        ]);

        return back()->with('success', 'Invoice marked as paid. Receipt and ledger updated.');
    }

    public function pdf(Request $request, Invoice $invoice)
    {
        return DocumentExport::sendPdf(
            DocumentExport::invoicePdf($invoice),
            $invoice->invoice_number.'.pdf',
            $request->boolean('inline')
        );
    }

    public function emailForm(Invoice $invoice)
    {
        $invoice->loadMissing('client');

        return view('emails.send', [
            'heading' => 'Email invoice '.$invoice->invoice_number,
            'action' => route('invoices.email.send', $invoice),
            'to' => $invoice->recipientEmail(),
            'defaultMessage' => 'Please find attached invoice '.$invoice->invoice_number.' amounting to '.money_text($invoice->total).'.',
            'back' => route('invoices.show', $invoice),
        ]);
    }

    public function sendEmail(Request $request, Invoice $invoice)
    {
        $data = $request->validate([
            'to' => 'required|email',
            'message' => 'nullable|string',
        ]);
        $result = \App\Support\DocumentMailer::sendInvoice($invoice, $data['to'], $data['message'] ?? null);
        if (! $result['success']) {
            return back()->withInput()->with('error', 'Email failed: '.$result['error']);
        }
        if (in_array($invoice->status, ['draft', 'Draft', 'Unpaid', 'unpaid'], true)) {
            $invoice->update(['status' => 'sent']);
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice emailed to '.$data['to']);
    }

    public function docx(Invoice $invoice)
    {
        $path = DocumentExport::invoiceDocx($invoice);

        return response()->download($path, $invoice->invoice_number.'.docx')->deleteFileAfterSend(true);
    }

    protected function persist(Request $request, Invoice $invoice): Invoice
    {
        $request->validate([
            'date' => 'required|date',
            'due_date' => 'nullable|date',
            'status' => 'required|string',
            'items' => 'required|array|min:1',
        ]);

        $calc = LineTotals::compute($request->items ?? [], (float) $request->tax_rate, (float) $request->discount);
        $client = $request->client_id ? Client::find($request->client_id) : null;
        $isNew = ! $invoice->exists;

        $invoice->fill([
            'client_id' => $request->client_id ?: null,
            'client_name' => $client?->name ?: $request->client_name,
            'client_contact' => $client?->phone ?: $request->client_contact,
            'date' => $request->date,
            'due_date' => $request->due_date,
            'status' => $request->status,
            'notes' => $request->notes,
            'subtotal' => $calc['subtotal'],
            'tax' => $calc['tax'],
            'discount' => (float) $request->discount,
            'total' => $calc['total'],
        ]);
        if ($isNew) {
            $invoice->invoice_number = DocumentNumber::next('INV', 'invoices', 'invoice_number', auth()->user()->company_id);
            $invoice->pay_token = \Illuminate\Support\Str::random(48);
        }
        $invoice->project_id = $request->project_id ?: null;
        $invoice->is_recurring = $request->boolean('is_recurring');
        $invoice->recurrence_frequency = $request->get('recurrence_frequency');
        $invoice->next_recurrence_date = $request->get('next_recurrence_date');
        $invoice->save();
        $invoice->items()->delete();
        foreach ($calc['items'] as $item) {
            $invoice->items()->create([
                'item_name' => $item['name'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
            ]);
        }
        LedgerService::postInvoice($invoice);

        return $invoice;
    }
}
