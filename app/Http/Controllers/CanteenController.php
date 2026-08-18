<?php

namespace App\Http\Controllers;

use App\Models\CanteenItem;
use App\Models\CanteenMeal;
use App\Models\ChangeRequest;
use App\Support\CanteenService;
use App\Support\ChangeRequestService;
use Illuminate\Http\Request;

class CanteenController extends Controller
{
    public function today()
    {
        $meal = auth()->user()->todaysMeal();
        if ($meal && in_array($meal->status, ['pending', 'approved', 'posted'], true)) {
            return redirect()->route('canteen.show', $meal);
        }

        $items = CanteenItem::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()->groupBy('type');

        return view('canteen.today', [
            'items' => $items,
            'meal' => $meal,
            'date' => now(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
            'did_not_eat' => 'nullable|boolean',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:canteen_items,id',
            'qty' => 'nullable|array',
        ]);

        $meal = CanteenService::submit(
            auth()->user(),
            now(),
            CanteenService::selectedLines($data),
            $data['notes'] ?? null
        );

        return redirect()->route('canteen.show', $meal)->with('success', 'Today’s canteen entry was sent for review.');
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $base = CanteenMeal::with(['user', 'lines'])
            ->when($user->seesOnlyOwnRecords(), fn ($query) => $query->where('user_id', $user->id))
            ->when($request->status, fn ($query) => $query->where('status', $request->status))
            ->when($request->from, fn ($query) => $query->whereDate('meal_date', '>=', $request->from))
            ->when($request->to, fn ($query) => $query->whereDate('meal_date', '<=', $request->to))
            ->when($request->user_id && ! $user->seesOnlyOwnRecords(), fn ($query) => $query->where('user_id', $request->user_id));

        $meals = (clone $base)->latest('meal_date')->paginate(20)->withQueryString();
        $monthTotal = (clone $base)->whereMonth('meal_date', now()->month)->whereYear('meal_date', now()->year)->sum('total');

        return view('canteen.index', compact('meals', 'monthTotal'));
    }

    public function show(CanteenMeal $meal)
    {
        $this->authorizeMeal($meal);
        $meal->load(['user', 'lines', 'reviewer']);
        $pendingRequest = ChangeRequest::query()
            ->where('entity_type', 'CanteenMeal')
            ->where('entity_id', $meal->id)
            ->where('status', 'pending')
            ->latest()
            ->first();

        return view('canteen.show', compact('meal', 'pendingRequest'));
    }

    public function requestForm(CanteenMeal $meal)
    {
        $this->authorizeMeal($meal);
        abort_unless($meal->isLocked() && $meal->status !== 'posted', 403, 'This entry cannot be edited.');
        abort_if(
            ChangeRequest::query()->where('entity_type', 'CanteenMeal')->where('entity_id', $meal->id)->where('status', 'pending')->exists(),
            403,
            'A change request is already waiting for review.'
        );

        $items = CanteenItem::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()->groupBy('type');
        $selected = $meal->lines->pluck('qty', 'canteen_item_id');

        return view('canteen.request', compact('meal', 'items', 'selected'));
    }

    public function requestStore(Request $request, CanteenMeal $meal)
    {
        $this->authorizeMeal($meal);
        abort_unless($meal->user_id === auth()->id() || auth()->user()->isReviewer(), 403);
        abort_unless($meal->status !== 'posted', 403);

        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
            'did_not_eat' => 'nullable|boolean',
            'item_ids' => 'nullable|array',
            'item_ids.*' => 'integer|exists:canteen_items,id',
            'qty' => 'nullable|array',
        ]);

        $selected = CanteenService::selectedLines($data);
        if ($selected === []) {
            return back()->withErrors(['item_ids' => 'Select the corrected source and any foods that came with it.']);
        }

        $payloadItems = [];
        foreach ($selected as $id => $qty) {
            $payloadItems[] = ['item_id' => $id, 'qty' => $qty];
        }

        ChangeRequestService::open(
            'CanteenMeal',
            $meal->id,
            $data['reason'],
            [
                'notes' => $data['notes'] ?? $meal->notes,
                'items' => $payloadItems,
            ],
            $meal->load('lines')->snapshot()
        );

        return redirect()->route('canteen.show', $meal)->with('success', 'Edit request sent. The entry stays unchanged until a reviewer approves it.');
    }

    protected function authorizeMeal(CanteenMeal $meal): void
    {
        abort_unless($meal->company_id === auth()->user()->company_id, 403);
        if (auth()->user()->seesOnlyOwnRecords()) {
            abort_unless($meal->user_id === auth()->id(), 403);
        }
    }
}
