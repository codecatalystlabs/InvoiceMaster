<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Receipt;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index()
    {
        $months = [];
        $labels = [];
        for ($i = 11; $i >= 0; $i--) {
            $ts = strtotime("first day of -$i months");
            $key = date('Y-m', $ts);
            $months[$key] = 0;
            $labels[$key] = date('M Y', $ts);
        }

        $revenue = $months;
        $expenses = $months;
        $receipts = $months;

        foreach (Invoice::select(DB::raw("DATE_FORMAT(date, '%Y-%m') ym"), DB::raw('SUM(total) t'))->where('status', 'Paid')->where('date', '>=', now()->subMonths(11)->startOfMonth())->groupBy('ym')->pluck('t', 'ym') as $ym => $t) {
            if (isset($revenue[$ym])) {
                $revenue[$ym] = (float) $t;
            }
        }
        foreach (Expense::select(DB::raw("DATE_FORMAT(expense_date, '%Y-%m') ym"), DB::raw('SUM(amount) t'))->where('expense_date', '>=', now()->subMonths(11)->startOfMonth())->groupBy('ym')->pluck('t', 'ym') as $ym => $t) {
            if (isset($expenses[$ym])) {
                $expenses[$ym] = (float) $t;
            }
        }
        foreach (Receipt::select(DB::raw("DATE_FORMAT(issued_date, '%Y-%m') ym"), DB::raw('SUM(amount) t'))->where('issued_date', '>=', now()->subMonths(11)->startOfMonth())->groupBy('ym')->pluck('t', 'ym') as $ym => $t) {
            if (isset($receipts[$ym])) {
                $receipts[$ym] = (float) $t;
            }
        }

        $cats = Expense::select('category', DB::raw('SUM(amount) t'))->groupBy('category')->orderByDesc('t')->pluck('t', 'category');
        $status = Invoice::select('status', DB::raw('COUNT(*) c'))->groupBy('status')->pluck('c', 'status');
        $topClients = Invoice::query()
            ->select('client_id', DB::raw('SUM(total) t'))
            ->where('status', 'Paid')
            ->whereNotNull('client_id')
            ->groupBy('client_id')
            ->orderByDesc('t')
            ->limit(7)
            ->get()
            ->map(fn ($row) => [
                'name' => optional(Client::find($row->client_id))->name ?? 'Unknown',
                'total' => (float) $row->t,
            ]);

        $kpis = [
            'revenue' => (float) Invoice::whereIn('status', ['Paid', 'paid'])->sum('total'),
            'expenses' => (float) Expense::sum('amount'),
            'receipts' => (float) Receipt::sum('amount'),
            'receivable' => (float) Invoice::whereIn('status', ['Unpaid', 'unpaid', 'Partially Paid', 'Overdue', 'overdue', 'sent'])->sum('total'),
        ];
        $kpis['net'] = $kpis['revenue'] - $kpis['expenses'];

        $chart = [
            'labels' => array_values($labels),
            'revenue' => array_values($revenue),
            'expenses' => array_values($expenses),
            'receipts' => array_values($receipts),
            'profit' => array_map(fn ($r, $e) => $r - $e, array_values($revenue), array_values($expenses)),
            'catLabels' => $cats->keys()->values(),
            'catValues' => $cats->values()->map(fn ($v) => (float) $v),
            'statusLabels' => $status->keys()->values(),
            'statusCounts' => $status->values()->map(fn ($v) => (int) $v),
            'clientLabels' => $topClients->pluck('name'),
            'clientValues' => $topClients->pluck('total'),
            'currency' => auth()->user()->company->currency ?? 'UGX',
        ];

        return view('analytics.index', compact('kpis', 'chart'));
    }
}
