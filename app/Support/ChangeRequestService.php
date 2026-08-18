<?php

namespace App\Support;

use App\Models\CanteenMeal;
use App\Models\ChangeRequest;
use Illuminate\Validation\ValidationException;

class ChangeRequestService
{
    public static function open(string $entityType, int $entityId, string $reason, array $payload, array $snapshot, string $action = 'update'): ChangeRequest
    {
        $exists = ChangeRequest::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('status', 'pending')
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'request' => 'A change request is already waiting for review on this record.',
            ]);
        }

        $request = ChangeRequest::create([
            'user_id' => auth()->id(),
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'payload' => $payload,
            'snapshot' => $snapshot,
            'status' => 'pending',
            'reason' => $reason,
        ]);

        Audit::log(
            'RequestEdit',
            $entityType,
            $entityId,
            $reason,
            null,
            ['module' => 'requests', 'change_request_id' => $request->id]
        );

        return $request;
    }

    public static function approve(ChangeRequest $request, ?string $notes = null): ChangeRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['request' => 'This request is no longer pending.']);
        }

        if ($request->entity_type === 'CanteenMeal') {
            $meal = CanteenMeal::findOrFail($request->entity_id);
            if ($meal->status === 'posted') {
                throw ValidationException::withMessages(['request' => 'That meal is already posted to the month expense.']);
            }
            CanteenService::applyPayload($meal, $request->payload ?? []);
        }

        $request->update([
            'status' => 'approved',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        Audit::log(
            'ApproveEdit',
            $request->entity_type,
            $request->entity_id,
            $notes ?: 'Edit request approved',
            null,
            ['module' => 'requests', 'change_request_id' => $request->id]
        );

        return $request;
    }

    public static function refuse(ChangeRequest $request, ?string $notes = null): ChangeRequest
    {
        if ($request->status !== 'pending') {
            throw ValidationException::withMessages(['request' => 'This request is no longer pending.']);
        }

        $request->update([
            'status' => 'refused',
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
            'review_notes' => $notes,
        ]);

        Audit::log(
            'RefuseEdit',
            $request->entity_type,
            $request->entity_id,
            $notes ?: 'Edit request refused',
            null,
            ['module' => 'requests', 'change_request_id' => $request->id]
        );

        return $request;
    }
}
