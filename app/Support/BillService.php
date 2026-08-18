<?php

namespace App\Support;

use App\Models\Bill;
use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;

class BillService
{
    public static function save(Bill $bill, array $data, array $items): Bill
    {
        return DB::transaction(function () use ($bill, $data, $items) {
            $subtotal = 0;
            $clean = [];
            foreach ($items as $item) {
                $qty = (float) ($item['qty'] ?? 1);
                $price = (float) ($item['unit_price'] ?? 0);
                $name = trim((string) ($item['item_name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $total = $qty * $price;
                $subtotal += $total;
                $clean[] = compact('name', 'qty', 'price', 'total') + ['item_name' => $name, 'unit_price' => $price];
            }
            $tax = (float) ($data['tax'] ?? 0);
            $bill->fill($data);
            $bill->subtotal = $subtotal;
            $bill->tax = $tax;
            $bill->total = $subtotal + $tax;
            if (! $bill->number) {
                $bill->number = DocumentNumber::next('BILL', 'bills', 'number', $bill->company_id ?: auth()->user()->company_id);
            }
            $bill->save();
            $bill->items()->delete();
            foreach ($clean as $row) {
                $bill->items()->create($row);
            }
            self::postLedger($bill);

            return $bill->load('items');
        });
    }

    public static function postLedger(Bill $bill): void
    {
        $companyId = (int) $bill->company_id;
        $expense = $bill->account_id
            ? ChartOfAccount::withoutGlobalScopes()->find($bill->account_id)
            : ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->where('account_code', '5200')->first();
        $ap = ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->where('account_code', '2110')->first();
        $vat = ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->where('account_code', '1140')->first();
        if (! $expense || ! $ap) {
            return;
        }
        $lines = [
            ['account_id' => $expense->id, 'entry_type' => 'Debit', 'amount' => $bill->subtotal],
            ['account_id' => $ap->id, 'entry_type' => 'Credit', 'amount' => $bill->total],
        ];
        if ($vat && $bill->tax > 0) {
            $lines[] = ['account_id' => $vat->id, 'entry_type' => 'Debit', 'amount' => $bill->tax];
        }
        LedgerService::postJournal('Bill', $bill->id, $companyId, $bill->bill_date?->toDateString() ?? now()->toDateString(), $bill->number, 'Bill '.$bill->number.' — '.$bill->vendor_name, $lines);
    }

    public static function pay(Bill $bill, float $amount, string $method = 'bank'): Bill
    {
        $amount = min($amount, $bill->outstanding());
        CashBookService::record([
            'company_id' => $bill->company_id,
            'entry_date' => now()->toDateString(),
            'description' => 'Bill '.$bill->number.' — '.$bill->vendor_name,
            'type' => 'credit',
            'amount' => $amount,
            'payment_method' => $method,
            'account_id' => $bill->account_id,
        ]);
        $bill->amount_paid = (float) $bill->amount_paid + $amount;
        $bill->status = $bill->outstanding() <= 0.009 ? 'Paid' : 'Partially Paid';
        $bill->save();

        $companyId = (int) $bill->company_id;
        $ap = ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->where('account_code', '2110')->first();
        $cashCode = strtolower($method) === 'bank' ? '1120' : '1110';
        $cash = ChartOfAccount::withoutGlobalScopes()->where('company_id', $companyId)->where('account_code', $cashCode)->first();
        if ($ap && $cash) {
            LedgerService::postJournal('BillPay', $bill->id, $companyId, now()->toDateString(), $bill->number, 'Payment of bill '.$bill->number, [
                ['account_id' => $ap->id, 'entry_type' => 'Debit', 'amount' => $amount],
                ['account_id' => $cash->id, 'entry_type' => 'Credit', 'amount' => $amount],
            ]);
        }

        return $bill;
    }
}
