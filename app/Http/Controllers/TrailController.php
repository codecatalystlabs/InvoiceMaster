<?php

namespace App\Http\Controllers;

use App\Models\TransactionEvent;
use Illuminate\Http\Request;

class TrailController extends Controller
{
    public function index(Request $request)
    {
        $events = TransactionEvent::with('user')
            ->when($request->q, fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('event_type', 'like', '%'.$request->q.'%')
                    ->orWhere('entity_type', 'like', '%'.$request->q.'%')
                    ->orWhere('description', 'like', '%'.$request->q.'%')
                    ->orWhere('module', 'like', '%'.$request->q.'%');
            }))
            ->when($request->module, fn ($q) => $q->where('module', $request->module))
            ->when($request->from, fn ($q) => $q->whereDate('occurred_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('occurred_at', '<=', $request->to))
            ->latest('occurred_at')
            ->paginate(40)
            ->withQueryString();

        $modules = TransactionEvent::query()->select('module')->distinct()->orderBy('module')->pluck('module');

        return view('trail.index', compact('events', 'modules'));
    }
}
