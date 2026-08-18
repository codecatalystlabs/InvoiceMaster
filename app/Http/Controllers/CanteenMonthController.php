<?php

namespace App\Http\Controllers;

use App\Models\CanteenMeal;
use App\Models\CanteenMonthClose;
use App\Support\CanteenService;
use Illuminate\Http\Request;

class CanteenMonthController extends Controller
{
    public function show(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $meals = CanteenMeal::with(['user', 'lines'])
            ->whereYear('meal_date', $year)
            ->whereMonth('meal_date', $month)
            ->orderBy('meal_date')
            ->orderBy('user_id')
            ->get();

        $byPerson = $meals->whereIn('status', ['approved', 'posted'])->groupBy('user_id')->map(function ($rows) {
            $first = $rows->first();

            return [
                'name' => $first->user?->name,
                'count' => $rows->count(),
                'total' => $rows->sum('total'),
            ];
        })->values();

        $byItem = [];
        foreach ($meals->whereIn('status', ['approved', 'posted']) as $meal) {
            foreach ($meal->lines as $line) {
                $key = $line->item_name.'|'.$line->item_type;
                if (! isset($byItem[$key])) {
                    $byItem[$key] = ['name' => $line->item_name, 'type' => $line->item_type, 'qty' => 0, 'total' => 0];
                }
                $byItem[$key]['qty'] += $line->qty;
                $byItem[$key]['total'] += $line->line_total;
            }
        }
        $byItem = collect($byItem)->sortBy('name')->values();

        $close = CanteenMonthClose::with(['closer', 'expense'])->where('year', $year)->where('month', $month)->first();
        $pending = $meals->where('status', 'pending')->count();
        $approvedTotal = (float) $meals->where('status', 'approved')->sum('total');
        $postedTotal = (float) $meals->where('status', 'posted')->sum('total');

        return view('canteen.month', compact(
            'year', 'month', 'meals', 'byPerson', 'byItem', 'close', 'pending', 'approvedTotal', 'postedTotal'
        ));
    }

    public function close(Request $request)
    {
        $data = $request->validate([
            'year' => 'required|integer|min:2020',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $pending = CanteenMeal::query()
            ->whereYear('meal_date', $data['year'])
            ->whereMonth('meal_date', $data['month'])
            ->where('status', 'pending')
            ->count();
        if ($pending > 0) {
            return back()->with('error', $pending.' entries are still waiting for review. Approve or refuse them before closing the month.');
        }

        $close = CanteenService::closeMonth(auth()->user(), (int) $data['year'], (int) $data['month']);

        $target = auth()->user()->canAccess('expenses') && $close->expense_id
            ? redirect()->route('expenses.show', $close->expense_id)
            : redirect()->route('canteen.month', ['year' => $close->year, 'month' => $close->month]);

        return $target->with('success', 'Month closed. Canteen total posted as '.($close->expense->expense_number ?? money_text($close->total)).'.');
    }
}
