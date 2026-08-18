<?php

namespace App\Support;

use App\Models\CashBookEntry;
use App\Models\ChartOfAccount;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerEntry;
use App\Models\PettyCashEntry;
use App\Models\Receipt;
use App\Models\ServicePayment;
use Illuminate\Support\Facades\DB;

class LedgerService
{
    public static function postInvoice(Invoice $invoice): void
    {
        if (in_array(strtolower((string) $invoice->status), ['cancelled', 'canceled'], true)) {
            self::forget('Invoice', $invoice->id);

            return;
        }

        $companyId = (int) $invoice->company_id;
        $ar = self::code('1130', $companyId);
        $revenue = self::code('4100', $companyId);
        if (! $ar || ! $revenue) {
            return;
        }

        self::replace('Invoice', $invoice->id, $companyId, $invoice->date?->toDateString() ?? now()->toDateString(), $invoice->invoice_number, 'Invoice '.$invoice->invoice_number.' — '.$invoice->displayClient(), [
            ['account_id' => $ar->id, 'entry_type' => 'Debit', 'amount' => $invoice->total],
            ['account_id' => $revenue->id, 'entry_type' => 'Credit', 'amount' => $invoice->total],
        ]);
    }

    public static function postReceipt(Receipt $receipt): void
    {
        $companyId = (int) $receipt->company_id;
        $cash = self::cashAccount($receipt->payment_method, $companyId);
        $contra = $receipt->invoice_id ? self::code('1130', $companyId) : self::code('4100', $companyId);
        if (! $cash || ! $contra) {
            return;
        }

        self::replace('Receipt', $receipt->id, $companyId, $receipt->issued_date?->toDateString() ?? now()->toDateString(), $receipt->number, 'Receipt '.$receipt->number.' — '.$receipt->client_name, [
            ['account_id' => $cash->id, 'entry_type' => 'Debit', 'amount' => $receipt->amount],
            ['account_id' => $contra->id, 'entry_type' => 'Credit', 'amount' => $receipt->amount],
        ]);
    }

    public static function postExpense(Expense $expense): void
    {
        $companyId = (int) $expense->company_id;
        $expenseAccount = $expense->account_id
            ? ChartOfAccount::withoutGlobalScopes()->find($expense->account_id)
            : self::code('5000', $companyId);
        if (! $expenseAccount) {
            $expenseAccount = self::code('5110', $companyId);
        }
        $paid = strcasecmp((string) $expense->payment_status, 'Paid') === 0
            || strcasecmp((string) $expense->payment_status, 'paid') === 0;
        $contra = $paid
            ? self::cashAccount($expense->payment_method, $companyId)
            : self::code('2110', $companyId);
        if (! $expenseAccount || ! $contra) {
            return;
        }

        self::replace('Expense', $expense->id, $companyId, $expense->expense_date?->toDateString() ?? now()->toDateString(), $expense->expense_number, 'Expense '.$expense->expense_number.' — '.$expense->vendor_name, [
            ['account_id' => $expenseAccount->id, 'entry_type' => 'Debit', 'amount' => $expense->amount],
            ['account_id' => $contra->id, 'entry_type' => 'Credit', 'amount' => $expense->amount],
        ]);
    }

    public static function postServicePayment(ServicePayment $payment): void
    {
        $payment->loadMissing('service');
        $companyId = (int) ($payment->service?->company_id ?? auth()->user()?->company_id);
        if (! $companyId) {
            return;
        }
        $expense = self::code('5200', $companyId);
        $cash = self::cashAccount($payment->payment_method, $companyId);
        if (! $expense || ! $cash) {
            return;
        }
        $ref = $payment->reference_number ?: ('SRV-PAY-'.$payment->id);
        $desc = 'Service payment — '.($payment->service?->service_name ?? $ref);

        self::replace('Service', $payment->id, $companyId, $payment->payment_date?->toDateString() ?? now()->toDateString(), $ref, $desc, [
            ['account_id' => $expense->id, 'entry_type' => 'Debit', 'amount' => $payment->amount],
            ['account_id' => $cash->id, 'entry_type' => 'Credit', 'amount' => $payment->amount],
        ]);
    }

    public static function postCashBook(CashBookEntry $entry): void
    {
        if ($entry->expense_id || $entry->service_id) {
            return;
        }
        if (str_starts_with((string) $entry->description, 'Receipt ')) {
            return;
        }

        $companyId = (int) $entry->company_id;
        $cash = self::cashAccount($entry->payment_method, $companyId);
        if (! $cash) {
            return;
        }

        if ($entry->invoice_id) {
            $hasReceipt = Receipt::withoutGlobalScopes()
                ->where('company_id', $companyId)
                ->where('invoice_id', $entry->invoice_id)
                ->exists();
            if ($hasReceipt) {
                return;
            }
            $ar = self::code('1130', $companyId);
            if (! $ar) {
                return;
            }
            self::replace('Cashbook', $entry->id, $companyId, $entry->entry_date?->toDateString() ?? now()->toDateString(), $entry->number, $entry->description, [
                ['account_id' => $cash->id, 'entry_type' => 'Debit', 'amount' => $entry->amount],
                ['account_id' => $ar->id, 'entry_type' => 'Credit', 'amount' => $entry->amount],
            ]);

            return;
        }

        $other = $entry->account_id
            ? ChartOfAccount::withoutGlobalScopes()->find($entry->account_id)
            : ($entry->type === 'debit' ? self::code('4100', $companyId) : self::code('5000', $companyId));
        if (! $other) {
            $other = $entry->type === 'debit' ? self::code('4100', $companyId) : self::code('5110', $companyId);
        }
        if (! $other) {
            return;
        }

        $lines = $entry->type === 'debit'
            ? [
                ['account_id' => $cash->id, 'entry_type' => 'Debit', 'amount' => $entry->amount],
                ['account_id' => $other->id, 'entry_type' => 'Credit', 'amount' => $entry->amount],
            ]
            : [
                ['account_id' => $other->id, 'entry_type' => 'Debit', 'amount' => $entry->amount],
                ['account_id' => $cash->id, 'entry_type' => 'Credit', 'amount' => $entry->amount],
            ];

        self::replace('Cashbook', $entry->id, $companyId, $entry->entry_date?->toDateString() ?? now()->toDateString(), $entry->number, $entry->description, $lines);
    }

    public static function postJournal(string $sourceType, int $sourceId, int $companyId, string $date, string $reference, string $description, array $lines): void
    {
        self::replace($sourceType, $sourceId, $companyId, $date, $reference, $description, $lines);
    }

    public static function forget(string $sourceType, int $sourceId): void
    {
        LedgerEntry::withoutGlobalScopes()
            ->where('source_type', $sourceType)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public static function rebuild(?int $companyId = null): int
    {
        $companyId = $companyId ?? auth()->user()?->company_id;
        if (! $companyId) {
            return 0;
        }

        return (int) DB::transaction(function () use ($companyId) {
            LedgerEntry::withoutGlobalScopes()->where('company_id', $companyId)->delete();

            foreach (Invoice::withoutGlobalScopes()->where('company_id', $companyId)->get() as $invoice) {
                self::postInvoice($invoice);
            }
            foreach (Receipt::withoutGlobalScopes()->where('company_id', $companyId)->get() as $receipt) {
                self::postReceipt($receipt);
            }
            foreach (Expense::withoutGlobalScopes()->where('company_id', $companyId)->get() as $expense) {
                self::postExpense($expense);
            }
            $serviceIds = \App\Models\Service::withoutGlobalScopes()->where('company_id', $companyId)->pluck('id');
            foreach (ServicePayment::with('service')->whereIn('service_id', $serviceIds)->get() as $payment) {
                self::postServicePayment($payment);
            }
            foreach (CashBookEntry::withoutGlobalScopes()->where('company_id', $companyId)->orderBy('id')->get() as $entry) {
                self::postCashBook($entry);
            }
            foreach (\App\Models\Bill::withoutGlobalScopes()->where('company_id', $companyId)->get() as $bill) {
                \App\Support\BillService::postLedger($bill);
            }
            foreach (\App\Models\PayrollRun::withoutGlobalScopes()->where('company_id', $companyId)->where('status', 'posted')->get() as $run) {
                \App\Support\PayrollService::writeLedger($run);
            }
            foreach (PettyCashEntry::withoutGlobalScopes()->where('company_id', $companyId)->whereIn('type', ['allocation', 'replenish'])->get() as $entry) {
                self::postPettyCashTopup($entry);
            }

            return LedgerEntry::withoutGlobalScopes()->where('company_id', $companyId)->count();
        });
    }

    protected static function replace(string $sourceType, int $sourceId, int $companyId, string $date, string $reference, string $description, array $lines): void
    {
        self::forget($sourceType, $sourceId);

        foreach ($lines as $line) {
            if ((float) $line['amount'] <= 0 || empty($line['account_id'])) {
                continue;
            }
            LedgerEntry::withoutGlobalScopes()->create([
                'company_id' => $companyId,
                'entry_date' => $date,
                'reference_number' => $reference,
                'account_id' => $line['account_id'],
                'entry_type' => $line['entry_type'],
                'amount' => $line['amount'],
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'created_by' => auth()->id(),
            ]);
        }
    }

    public static function postPettyCashTopup(PettyCashEntry $entry): void
    {
        if (! in_array($entry->type, ['allocation', 'replenish'], true)) {
            return;
        }
        $companyId = (int) $entry->company_id;
        $tin = self::code('1115', $companyId) ?: self::code('1110', $companyId);
        $source = self::code('1120', $companyId) ?: self::code('1110', $companyId);
        if (! $tin || ! $source || $tin->id === $source->id) {
            return;
        }

        self::replace('PettyCash', $entry->id, $companyId, $entry->entry_date?->toDateString() ?? now()->toDateString(), $entry->number, $entry->description, [
            ['account_id' => $tin->id, 'entry_type' => 'Debit', 'amount' => $entry->amount],
            ['account_id' => $source->id, 'entry_type' => 'Credit', 'amount' => $entry->amount],
        ]);
    }

    protected static function cashAccount(?string $method, int $companyId): ?ChartOfAccount
    {
        $code = match (strtolower((string) $method)) {
            'bank', 'cheque', 'check', 'transfer' => '1120',
            'petty_cash', 'petty cash' => '1115',
            default => '1110',
        };

        return self::code($code, $companyId) ?: self::code('1110', $companyId);
    }

    protected static function code(string $code, int $companyId): ?ChartOfAccount
    {
        return ChartOfAccount::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('account_code', $code)
            ->first();
    }
}
