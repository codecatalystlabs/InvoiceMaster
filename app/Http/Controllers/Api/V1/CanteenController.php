<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CanteenItem;
use App\Models\CanteenMeal;
use App\Models\ChangeRequest;
use App\Support\CanteenService;
use App\Support\ChangeRequestService;
use Illuminate\Http\Request;

class CanteenController extends Controller
{
    public function catalog()
    {
        abort_unless(auth()->user()->canAccess('canteen'), 403);
        $items = CanteenItem::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get()
            ->groupBy('type')
            ->map(fn ($rows) => $rows->map(fn ($item) => [
                'id' => $item->id,
                'name' => $item->name,
                'type' => $item->type,
                'unit' => $item->unit,
                'price' => $item->is_priced ? (float) $item->price : 0,
                'is_priced' => (bool) $item->is_priced,
                'included' => ! $item->is_priced,
            ])->values());

        return response()->json([
            'types' => CanteenItem::types(),
            'note' => 'Charge the source (chicken, beans, and so on). Foods served with it are included in that price.',
            'items' => $items,
        ]);
    }

    public function meals(Request $request)
    {
        abort_unless(auth()->user()->canAccess('canteen'), 403);
        $user = auth()->user();
        $rows = CanteenMeal::with('lines')
            ->when($user->seesOnlyOwnRecords(), fn ($q) => $q->where('user_id', $user->id))
            ->latest('meal_date')
            ->paginate(20);

        return response()->json($rows->through(fn ($meal) => $this->mealPayload($meal)));
    }

    public function today()
    {
        abort_unless(auth()->user()->canAccess('canteen'), 403);
        $meal = auth()->user()->todaysMeal();

        return response()->json([
            'declared' => (bool) $meal,
            'meal' => $meal ? $this->mealPayload($meal->load('lines')) : null,
        ]);
    }

    public function store(Request $request)
    {
        abort_unless(auth()->user()->canAccess('canteen'), 403);
        $data = $request->validate([
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer|exists:canteen_items,id',
            'items.*.qty' => 'nullable|numeric|min:1',
        ]);
        $selected = [];
        foreach ($data['items'] as $row) {
            $selected[(int) $row['item_id']] = (float) ($row['qty'] ?? 1);
        }
        $meal = CanteenService::submit(auth()->user(), now(), $selected, $data['notes'] ?? null);

        return response()->json(['meal' => $this->mealPayload($meal)], 201);
    }

    public function show(CanteenMeal $meal)
    {
        abort_unless(auth()->user()->canAccess('canteen'), 403);
        if (auth()->user()->seesOnlyOwnRecords()) {
            abort_unless($meal->user_id === auth()->id(), 403);
        }

        return response()->json(['meal' => $this->mealPayload($meal->load('lines', 'reviewer', 'user'))]);
    }

    public function requestEdit(Request $request, CanteenMeal $meal)
    {
        abort_unless($meal->user_id === auth()->id(), 403);
        $data = $request->validate([
            'reason' => 'required|string|max:500',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_id' => 'required|integer',
            'items.*.qty' => 'nullable|numeric|min:1',
        ]);
        $req = ChangeRequestService::open('CanteenMeal', $meal->id, $data['reason'], [
            'notes' => $data['notes'] ?? $meal->notes,
            'items' => $data['items'],
        ], $meal->load('lines')->snapshot());

        return response()->json(['request' => ['id' => $req->id, 'status' => $req->status]], 201);
    }

    public function review(Request $request)
    {
        abort_unless(auth()->user()->canAccess('canteen.review'), 403);
        $date = $request->get('date', now()->toDateString());
        $pending = CanteenMeal::with(['user', 'lines'])->whereDate('meal_date', $date)->where('status', 'pending')->get();

        return response()->json([
            'date' => $date,
            'pending' => $pending->map(fn ($m) => $this->mealPayload($m)),
        ]);
    }

    public function approve(CanteenMeal $meal)
    {
        abort_unless(auth()->user()->canAccess('canteen.review'), 403);
        abort_unless($meal->status === 'pending', 422);
        $meal->update(['status' => 'approved', 'reviewed_by' => auth()->id(), 'reviewed_at' => now()]);

        return response()->json(['meal' => $this->mealPayload($meal->fresh('lines'))]);
    }

    public function refuse(Request $request, CanteenMeal $meal)
    {
        abort_unless(auth()->user()->canAccess('canteen.review'), 403);
        $data = $request->validate(['review_notes' => 'required|string|max:500']);
        $meal->update(['status' => 'refused', 'reviewed_by' => auth()->id(), 'reviewed_at' => now(), 'review_notes' => $data['review_notes']]);

        return response()->json(['meal' => $this->mealPayload($meal->fresh('lines'))]);
    }

    protected function mealPayload(CanteenMeal $meal): array
    {
        return [
            'id' => $meal->id,
            'date' => $meal->meal_date?->toDateString(),
            'status' => $meal->status,
            'total' => (float) $meal->total,
            'notes' => $meal->notes,
            'user' => $meal->user?->only(['id', 'name']),
            'lines' => $meal->lines->map(fn ($l) => [
                'item_id' => $l->canteen_item_id,
                'name' => $l->item_name,
                'type' => $l->item_type,
                'qty' => (float) $l->qty,
                'unit_price' => (float) $l->unit_price,
                'line_total' => (float) $l->line_total,
                'included' => (float) $l->unit_price == 0.0,
            ]),
        ];
    }
}
