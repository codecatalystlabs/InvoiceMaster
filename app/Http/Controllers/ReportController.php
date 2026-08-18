<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CashBookEntry;
use App\Models\Expense;
use App\Models\Invoice;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function financial(Request $request)
    {
        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->toDateString());

        $revenue = (float) Invoice::whereIn('status', ['Paid', 'paid'])->whereBetween('date', [$from, $to])->sum('total');
        $expenseRows = Expense::select('category', \Illuminate\Support\Facades\DB::raw('SUM(amount) as total'))
            ->whereBetween('expense_date', [$from, $to])
            ->groupBy('category')
            ->orderByDesc('total')
            ->get();
        $totalExpenses = (float) $expenseRows->sum('total');
        $net = $revenue - $totalExpenses;

        $assets = (float) Asset::whereDate('purchase_date', '<=', $to)->sum('current_value');
        $cash = (float) (CashBookEntry::whereDate('entry_date', '<=', $to)->orderByDesc('id')->value('balance_after') ?? 0);
        $receivable = (float) Invoice::whereIn('status', ['Unpaid', 'unpaid', 'Partially Paid', 'sent'])->whereDate('date', '<=', $to)->sum('total');
        $payable = (float) Expense::whereIn('payment_status', ['Pending', 'Partially Paid'])->whereDate('expense_date', '<=', $to)->sum('amount');
        $totalAssets = $assets + $cash + $receivable;
        $equity = $totalAssets - $payable;

        return view('reports.financial', compact(
            'from', 'to', 'revenue', 'expenseRows', 'totalExpenses', 'net',
            'assets', 'cash', 'receivable', 'payable', 'totalAssets', 'equity'
        ));
    }
}
