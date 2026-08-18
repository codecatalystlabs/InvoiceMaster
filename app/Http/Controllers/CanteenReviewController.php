<?php

namespace App\Http\Controllers;

use App\Models\CanteenItem;
use App\Models\CanteenMeal;
use App\Models\ChangeRequest;
use App\Support\Audit;
use Illuminate\Http\Request;

class CanteenReviewController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $pending = CanteenMeal::with(['user', 'lines'])
            ->whereDate('meal_date', $date)
            ->where('status', 'pending')
            ->orderBy('submitted_at')
            ->get();
        $done = CanteenMeal::with(['user', 'reviewer'])
            ->whereDate('meal_date', $date)
            ->whereIn('status', ['approved', 'refused', 'posted'])
            ->orderBy('user_id')
            ->get();
        $editRequests = ChangeRequest::with('requester')
            ->where('status', 'pending')
            ->latest()
            ->limit(20)
            ->get();

        return view('canteen.review', compact('date', 'pending', 'done', 'editRequests'));
    }

    public function approve(Request $request, CanteenMeal $meal)
    {
        $this->guardPending($meal);
        $meal->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $request->input('review_notes'),
        ]);
        Audit::log('Approve', 'CanteenMeal', $meal->id, $meal->user?->name.' '.$meal->meal_date?->toDateString(), $meal->total, ['module' => 'canteen.review']);

        return back()->with('success', 'Meal approved.');
    }

    public function refuse(Request $request, CanteenMeal $meal)
    {
        $this->guardPending($meal);
        $data = $request->validate(['review_notes' => 'required|string|max:500']);
        $meal->update([
            'status' => 'refused',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $data['review_notes'],
        ]);
        Audit::log('Refuse', 'CanteenMeal', $meal->id, $data['review_notes'], $meal->total, ['module' => 'canteen.review']);

        return back()->with('success', 'Meal refused. The person can declare again.');
    }

    public function bulkApprove(Request $request)
    {
        $date = $request->validate(['date' => 'required|date'])['date'];
        $meals = CanteenMeal::whereDate('meal_date', $date)->where('status', 'pending')->get();
        foreach ($meals as $meal) {
            $meal->update([
                'status' => 'approved',
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ]);
            Audit::log('Approve', 'CanteenMeal', $meal->id, 'Bulk approve '.$date, $meal->total, ['module' => 'canteen.review']);
        }

        return back()->with('success', $meals->count().' meals approved.');
    }

    public function catalog()
    {
        $items = CanteenItem::orderBy('sort_order')->orderBy('name')->get()->groupBy('type');
        $types = CanteenItem::types();

        return view('canteen.catalog', compact('items', 'types'));
    }

    public function storeItem(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:food,sauce,drink,extra',
            'unit' => 'required|string|max:30',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_priced' => 'nullable|boolean',
        ]);
        $data['is_active'] = true;
        $data['is_priced'] = $request->boolean('is_priced', $data['type'] !== 'food');
        if (! $data['is_priced']) {
            $data['price'] = 0;
        }
        $item = CanteenItem::create($data);
        Audit::log('Create', 'CanteenItem', $item->id, $item->name.' · '.money($item->price), $item->price, ['module' => 'canteen.catalog']);

        return back()->with('success', 'Item added to the catalog.');
    }

    public function updateItem(Request $request, CanteenItem $canteenItem)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'type' => 'required|in:food,sauce,drink,extra',
            'unit' => 'required|string|max:30',
            'price' => 'required|numeric|min:0',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'required|boolean',
            'is_priced' => 'nullable|boolean',
        ]);
        $data['is_priced'] = $request->boolean('is_priced');
        if (! $data['is_priced']) {
            $data['price'] = 0;
        }
        $canteenItem->update($data);
        Audit::log('Update', 'CanteenItem', $canteenItem->id, $canteenItem->name.' · '.money($canteenItem->price), $canteenItem->price, ['module' => 'canteen.catalog']);

        return back()->with('success', 'Catalog item updated.');
    }

    public function destroyItem(CanteenItem $canteenItem)
    {
        Audit::log('Delete', 'CanteenItem', $canteenItem->id, $canteenItem->name, $canteenItem->price, ['module' => 'canteen.catalog']);
        $canteenItem->delete();

        return back()->with('success', 'Catalog item removed.');
    }

    protected function guardPending(CanteenMeal $meal): void
    {
        abort_unless($meal->status === 'pending', 403, 'This entry is not waiting for review.');
    }
}
