<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CashBookEntry;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Service;
use App\Support\Audit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function index()
    {
        return view('exports.index');
    }

    public function download(Request $request, string $type): StreamedResponse
    {
        $from = $request->get('from');
        $to = $request->get('to');

        $map = [
            'invoices' => [fn () => Invoice::with('client')->when($from, fn ($q) => $q->whereDate('date', '>=', $from))->when($to, fn ($q) => $q->whereDate('date', '<=', $to))->get(), ['Number', 'Date', 'Client', 'Total', 'Status'], fn ($r) => [$r->invoice_number, $r->date?->toDateString(), $r->displayClient(), $r->total, $r->status]],
            'quotations' => [fn () => Quotation::with('client')->when($from, fn ($q) => $q->whereDate('date', '>=', $from))->when($to, fn ($q) => $q->whereDate('date', '<=', $to))->get(), ['Number', 'Date', 'Client', 'Total', 'Status'], fn ($r) => [$r->quotation_number, $r->date?->toDateString(), $r->client?->name, $r->total, $r->status]],
            'receipts' => [fn () => Receipt::when($from, fn ($q) => $q->whereDate('issued_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('issued_date', '<=', $to))->get(), ['Number', 'Date', 'Client', 'Amount', 'Method'], fn ($r) => [$r->number, $r->issued_date?->toDateString(), $r->client_name, $r->amount, $r->payment_method]],
            'clients' => [fn () => Client::orderBy('name')->get(), ['Name', 'Email', 'Phone', 'Company'], fn ($r) => [$r->name, $r->email, $r->phone, $r->company]],
            'expenses' => [fn () => Expense::when($from, fn ($q) => $q->whereDate('expense_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('expense_date', '<=', $to))->get(), ['Number', 'Date', 'Vendor', 'Category', 'Amount', 'Status'], fn ($r) => [$r->expense_number, $r->expense_date?->toDateString(), $r->vendor_name, $r->category, $r->amount, $r->payment_status]],
            'assets' => [fn () => Asset::get(), ['Number', 'Name', 'Category', 'Purchase', 'Value'], fn ($r) => [$r->asset_number, $r->asset_name, $r->category, $r->purchase_price, $r->current_value]],
            'services' => [fn () => Service::get(), ['Number', 'Name', 'Provider', 'Cost', 'Status'], fn ($r) => [$r->service_number, $r->service_name, $r->provider_name, $r->cost, $r->status]],
            'cashbook' => [fn () => CashBookEntry::when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))->orderBy('id')->get(), ['Number', 'Date', 'Description', 'Type', 'Amount', 'Balance'], fn ($r) => [$r->number, $r->entry_date?->toDateString(), $r->description, $r->type, $r->amount, $r->balance_after]],
            'ledger' => [fn () => LedgerEntry::with('account')->when($from, fn ($q) => $q->whereDate('entry_date', '>=', $from))->when($to, fn ($q) => $q->whereDate('entry_date', '<=', $to))->get(), ['Date', 'Ref', 'Account', 'Type', 'Amount'], fn ($r) => [$r->entry_date?->toDateString(), $r->reference_number, $r->account?->account_name, $r->entry_type, $r->amount]],
            'canteen' => [fn () => \App\Models\CanteenMeal::with(['user', 'lines'])
                ->when(auth()->user()->seesOnlyOwnRecords(), fn ($q) => $q->where('user_id', auth()->id()))
                ->when($from, fn ($q) => $q->whereDate('meal_date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('meal_date', '<=', $to))->get(), ['Date', 'Person', 'Items', 'Total', 'Status'], fn ($r) => [$r->meal_date?->toDateString(), $r->user?->name, $r->did_not_eat ? 'Did not eat' : $r->lines->map(fn ($l) => $l->item_name.'×'.$l->qty)->join('; '), $r->total, $r->status]],
        ];

        $moduleMap = [
            'invoices' => 'invoices',
            'quotations' => 'quotations',
            'receipts' => 'receipts',
            'clients' => 'clients',
            'expenses' => 'expenses',
            'assets' => 'assets',
            'services' => 'services',
            'cashbook' => 'cashbook',
            'ledger' => 'ledger',
            'canteen' => 'canteen',
        ];
        abort_unless(isset($map[$type]), 404);
        abort_unless(auth()->user()->canAccess($moduleMap[$type] ?? $type), 403);
        [$fetcher, $headers, $rowFn] = $map[$type];
        $rows = $fetcher();
        Audit::log('Export', ucfirst($type), null, $rows->count().' rows');

        return response()->streamDownload(function () use ($headers, $rows, $rowFn) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $rowFn($row));
            }
            fclose($out);
        }, $type.'_export_'.now()->toDateString().'.csv', [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }
}
