<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionStep extends Model
{
    protected $fillable = [
        'requisition_id', 'step', 'user_id', 'notes', 'amount',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
