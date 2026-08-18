<?php

namespace App\Support;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Service;
use Carbon\Carbon;

class RecurringService
{
    public static function generateInvoices(?int $companyId = null): int
    {
        $count = 0;
        $query = Invoice::withoutGlobalScopes()
            ->where('is_recurring', true)
            ->whereNotNull('next_recurrence_date')
            ->whereDate('next_recurrence_date', '<=', now()->toDateString());
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        foreach ($query->with('items')->get() as $template) {
            $clone = $template->replicate(['pay_token', 'amount_paid', 'status']);
            $clone->invoice_number = DocumentNumber::next('INV', 'invoices', 'invoice_number', $template->company_id);
            $clone->pay_token = \Illuminate\Support\Str::random(48);
            $clone->date = now()->toDateString();
            $clone->due_date = now()->addDays(30)->toDateString();
            $clone->status = 'Unpaid';
            $clone->amount_paid = 0;
            $clone->is_recurring = false;
            $clone->next_recurrence_date = null;
            $clone->recurrence_parent_id = $template->id;
            $clone->save();
            foreach ($template->items as $item) {
                $clone->items()->create($item->only(['item_name', 'qty', 'unit_price', 'total']));
            }
            LedgerService::postInvoice($clone);
            $template->next_recurrence_date = self::nextDate($template->next_recurrence_date, $template->recurrence_frequency);
            $template->save();
            $count++;
        }

        return $count;
    }

    public static function generateBillsFromServices(?int $companyId = null): int
    {
        $count = 0;
        $query = Service::withoutGlobalScopes()
            ->where('status', 'Active')
            ->where('auto_renew', true)
            ->whereNotNull('next_billing_date')
            ->whereDate('next_billing_date', '<=', now()->toDateString());
        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        foreach ($query->get() as $service) {
            $bill = new \App\Models\Bill([
                'company_id' => $service->company_id,
                'service_id' => $service->id,
                'vendor_name' => $service->provider_name,
                'bill_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'tax' => 0,
                'status' => 'Open',
                'notes' => 'Generated from service '.$service->service_number,
            ]);
            BillService::save($bill, $bill->toArray(), [[
                'item_name' => $service->service_name,
                'qty' => 1,
                'unit_price' => $service->cost,
            ]]);
            $service->next_billing_date = self::nextDate($service->next_billing_date, $service->billing_frequency);
            $service->save();
            $count++;
        }

        return $count;
    }

    public static function nextDate($from, ?string $frequency): string
    {
        $date = Carbon::parse($from);

        return match (strtolower((string) $frequency)) {
            'weekly' => $date->addWeek()->toDateString(),
            'quarterly' => $date->addMonths(3)->toDateString(),
            'yearly', 'annually' => $date->addYear()->toDateString(),
            default => $date->addMonth()->toDateString(),
        };
    }
}
