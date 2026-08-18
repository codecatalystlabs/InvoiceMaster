<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionEvent extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'module', 'event_type', 'entity_type', 'entity_id',
        'amount', 'description', 'meta', 'ip_address', 'user_agent', 'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'meta' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
