<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Quotation;
use App\Support\Audit;
use App\Support\DocumentExport;
use App\Support\DocumentNumber;
use App\Support\LineTotals;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->get('q');
        $status = $request->get('status');
        $quotations = Quotation::with('client')
            ->when($q, fn ($query) => $query->where('quotation_number', 'like', "%$q%")->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%$q%")))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('quotations.index', compact('quotations', 'q', 'status'));
    }

    public function create()
    {
        return view('quotations.form', ['quotation' => new Quotation(['date' => now()]), 'clients' => Client::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        $quotation = $this->persist($request, new Quotation);
        Audit::log('Create', 'Quotation', $quotation->id, $quotation->quotation_number);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation created.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['items', 'client']);

        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if ($quotation->status === 'Converted') {
            return back()->with('error', 'Converted quotations cannot be edited.');
        }
        $quotation->load('items');

        return view('quotations.form', ['quotation' => $quotation, 'clients' => Client::orderBy('name')->get()]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $this->persist($request, $quotation);
        Audit::log('Update', 'Quotation', $quotation->id, $quotation->quotation_number);

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation updated.');
    }

    public function destroy(Quotation $quotation)
    {
        Audit::log('Delete', 'Quotation', $quotation->id, $quotation->quotation_number);
        $quotation->delete();

        return redirect()->route('quotations.index')->with('success', 'Quotation deleted.');
    }

    public function convert(Quotation $quotation)
    {
        $invoice = Invoice::create([
            'quotation_id' => $quotation->id,
            'client_id' => $quotation->client_id,
            'invoice_number' => DocumentNumber::next('INV', 'invoices', 'invoice_number', auth()->user()->company_id),
            'client_name' => $quotation->client?->name,
            'date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'subtotal' => $quotation->subtotal,
            'tax' => $quotation->tax,
            'discount' => $quotation->discount,
            'total' => $quotation->total,
            'status' => 'Unpaid',
            'notes' => $quotation->notes,
        ]);
        foreach ($quotation->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'item_name' => $item->item_name,
                'qty' => $item->qty,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ]);
        }
        $quotation->update(['status' => 'Converted']);
        Audit::log('Convert', 'Quotation', $quotation->id, 'Converted to '.$invoice->invoice_number);

        return redirect()->route('invoices.show', $invoice)->with('success', 'Quotation converted to invoice.');
    }

    public function pdf(Request $request, Quotation $quotation)
    {
        return DocumentExport::sendPdf(
            DocumentExport::quotationPdf($quotation),
            $quotation->quotation_number.'.pdf',
            $request->boolean('inline')
        );
    }

    public function emailForm(Quotation $quotation)
    {
        $quotation->loadMissing('client');

        return view('emails.send', [
            'heading' => 'Email quotation '.$quotation->quotation_number,
            'action' => route('quotations.email.send', $quotation),
            'to' => $quotation->recipientEmail(),
            'defaultMessage' => 'Please find attached quotation '.$quotation->quotation_number.' amounting to '.money($quotation->total).'.',
            'back' => route('quotations.show', $quotation),
        ]);
    }

    public function sendEmail(Request $request, Quotation $quotation)
    {
        $data = $request->validate([
            'to' => 'required|email',
            'message' => 'nullable|string',
        ]);
        $result = \App\Support\DocumentMailer::sendQuotation($quotation, $data['to'], $data['message'] ?? null);
        if (! $result['success']) {
            return back()->withInput()->with('error', 'Email failed: '.$result['error']);
        }
        if ($quotation->status === 'Draft') {
            $quotation->update(['status' => 'Sent']);
        }

        return redirect()->route('quotations.show', $quotation)->with('success', 'Quotation emailed to '.$data['to']);
    }

    protected function persist(Request $request, Quotation $quotation): Quotation
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'date' => 'required|date',
            'status' => 'required|string',
            'tax_rate' => 'nullable|numeric',
            'discount' => 'nullable|numeric',
            'items' => 'required|array|min:1',
        ]);

        $calc = LineTotals::compute($request->items ?? [], (float) $request->tax_rate, (float) $request->discount);
        $isNew = ! $quotation->exists;
        $quotation->fill([
            'client_id' => $request->client_id,
            'date' => $request->date,
            'status' => $request->status,
            'notes' => $request->notes,
            'subtotal' => $calc['subtotal'],
            'tax' => $calc['tax'],
            'discount' => (float) $request->discount,
            'total' => $calc['total'],
        ]);
        if ($isNew) {
            $quotation->quotation_number = DocumentNumber::next('QUO', 'quotations', 'quotation_number', auth()->user()->company_id);
        }
        $quotation->save();
        $quotation->items()->delete();
        foreach ($calc['items'] as $item) {
            $quotation->items()->create([
                'item_name' => $item['name'],
                'qty' => $item['qty'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
            ]);
        }

        return $quotation;
    }
}
