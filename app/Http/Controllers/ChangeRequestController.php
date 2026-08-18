<?php

namespace App\Http\Controllers;

use App\Models\CanteenItem;
use App\Models\CanteenMeal;
use App\Models\ChangeRequest;
use App\Support\ChangeRequestService;
use Illuminate\Http\Request;

class ChangeRequestController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $rows = ChangeRequest::with(['requester', 'reviewer'])
            ->when($user->seesOnlyOwnRecords(), fn ($q) => $q->where('user_id', $user->id))
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('requests.index', compact('rows'));
    }

    public function show(ChangeRequest $changeRequest)
    {
        $user = auth()->user();
        if ($user->seesOnlyOwnRecords()) {
            abort_unless($changeRequest->user_id === $user->id, 403);
        }
        $changeRequest->load(['requester', 'reviewer']);
        $meal = $changeRequest->entity_type === 'CanteenMeal'
            ? CanteenMeal::with('lines')->find($changeRequest->entity_id)
            : null;
        $items = CanteenItem::query()->whereIn('id', collect($changeRequest->payload['items'] ?? [])->pluck('item_id'))->get()->keyBy('id');

        return view('requests.show', ['requestRow' => $changeRequest, 'meal' => $meal, 'items' => $items]);
    }

    public function approve(Request $request, ChangeRequest $changeRequest)
    {
        abort_unless(auth()->user()->canAccess('canteen.review'), 403);
        ChangeRequestService::approve($changeRequest, $request->input('review_notes'));

        return back()->with('success', 'Edit applied. The record now waits for a fresh review of the new totals.');
    }

    public function refuse(Request $request, ChangeRequest $changeRequest)
    {
        abort_unless(auth()->user()->canAccess('canteen.review'), 403);
        $data = $request->validate(['review_notes' => 'required|string|max:500']);
        ChangeRequestService::refuse($changeRequest, $data['review_notes']);

        return back()->with('success', 'Edit request refused. The original entry is unchanged.');
    }
}
