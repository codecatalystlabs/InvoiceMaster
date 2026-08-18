<?php

namespace App\Support;

use App\Models\Bill;
use App\Models\Expense;
use App\Models\Invoice;

class VatService
{
    public static function worksheet(int $companyId, string $from, string $to): array
    {
        $invoices = Invoice::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereBetween('date', [$from, $to])
            ->whereNotIn('status', ['Cancelled', 'cancelled', 'Draft', 'draft'])
            ->get();
        $output = (float) $invoices->sum('tax');
        $sales = (float) $invoices->sum('total');

        $inputExpense = (float) Expense::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereBetween('expense_date', [$from, $to])
            ->sum('tax');
        $inputBills = (float) Bill::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->whereBetween('bill_date', [$from, $to])
            ->sum('tax');
        $input = $inputExpense + $inputBills;

        return [
            'from' => $from,
            'to' => $to,
            'sales' => $sales,
            'output' => $output,
            'input' => $input,
            'net' => $output - $input,
            'invoices' => $invoices,
        ];
    }
}
