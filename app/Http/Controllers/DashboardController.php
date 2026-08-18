<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\CanteenMeal;
use App\Models\CashBookEntry;
use App\Models\ChangeRequest;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\Receipt;
use App\Models\Service;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user->canAccess('invoices')) {
            $mine = CanteenMeal::query()->where('user_id', $user->id);
            $stats = [
                'my_month' => (float) (clone $mine)->whereMonth('meal_date', now()->month)->whereYear('meal_date', now()->year)->sum('total'),
                'my_pending' => (clone $mine)->where('status', 'pending')->count(),
                'my_approved' => (clone $mine)->whereIn('status', ['approved', 'posted'])->whereMonth('meal_date', now()->month)->whereYear('meal_date', now()->year)->count(),
                'today' => $user->todaysMeal(),
            ];
            $recentMeals = CanteenMeal::with('lines')->where('user_id', $user->id)->latest('meal_date')->limit(10)->get();
            $pendingEdits = ChangeRequest::query()->where('user_id', $user->id)->where('status', 'pending')->count();

            return view('dashboard-staff', compact('stats', 'recentMeals', 'pendingEdits'));
        }

        $stats = [
            'quotations' => Quotation::count(),
            'invoices' => Invoice::count(),
            'clients' => Client::count(),
            'receipts' => Receipt::count(),
            'unpaid' => Invoice::whereIn('status', ['Unpaid', 'unpaid', 'sent', 'Overdue', 'overdue'])->count(),
            'revenue' => (float) Invoice::whereIn('status', ['Paid', 'paid'])->sum('total'),
            'pending' => (float) Invoice::whereIn('status', ['Unpaid', 'unpaid', 'Partially Paid', 'Overdue', 'overdue', 'sent'])->sum('total'),
            'receipts_total' => (float) Receipt::sum('amount'),
            'expenses_month' => (float) Expense::whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'),
            'assets' => (float) Asset::sum('current_value'),
            'services' => Service::where('status', 'Active')->count(),
            'cash_balance' => (float) (CashBookEntry::orderByDesc('id')->value('balance_after') ?? 0),
            'canteen_month' => (float) CanteenMeal::whereMonth('meal_date', now()->month)->whereYear('meal_date', now()->year)->whereIn('status', ['approved', 'posted'])->sum('total'),
            'canteen_pending' => CanteenMeal::where('status', 'pending')->count(),
        ];

        $recentInvoices = Invoice::with('client')->latest()->limit(8)->get();
        $recentQuotations = Quotation::with('client')->latest()->limit(8)->get();
        $recentReceipts = Receipt::latest()->limit(8)->get();

        return view('dashboard', compact('stats', 'recentInvoices', 'recentQuotations', 'recentReceipts'));
    }
}
