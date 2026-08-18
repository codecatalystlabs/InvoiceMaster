<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\TransactionEvent;
use Throwable;

class Audit
{
    public static function log(
        string $action,
        string $entityType,
        ?int $entityId = null,
        string $details = '',
        ?float $amount = null,
        array $meta = []
    ): void {
        if (! auth()->check()) {
            return;
        }

        $user = auth()->user();

        AuditLog::create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
        ]);

        try {
            TransactionEvent::create([
                'company_id' => $user->company_id,
                'user_id' => $user->id,
                'module' => $meta['module'] ?? $entityType,
                'event_type' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'amount' => $amount,
                'description' => $details,
                'meta' => $meta ?: null,
                'ip_address' => request()?->ip(),
                'user_agent' => substr((string) request()?->userAgent(), 0, 255),
                'occurred_at' => now(),
            ]);
        } catch (Throwable) {
            // Trail table may not exist yet during early setup.
        }
    }
}
