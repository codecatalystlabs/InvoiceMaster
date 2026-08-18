<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ChangeRequest extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'entity_type', 'entity_id', 'action',
        'payload', 'snapshot', 'status', 'reason', 'reviewed_by', 'reviewed_at', 'review_notes',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'snapshot' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function subject(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }

    public function meal(): BelongsTo
    {
        return $this->belongsTo(CanteenMeal::class, 'entity_id');
    }
}
