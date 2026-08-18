<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequisitionLine extends Model
{
    protected $fillable = [
        'requisition_id', 'spent_on', 'description', 'amount', 'receipt_path',
    ];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'amount' => 'float',
        ];
    }

    public function requisition(): BelongsTo
    {
        return $this->belongsTo(Requisition::class);
    }
}
