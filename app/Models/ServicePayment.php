<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServicePayment extends Model
{
    protected $fillable = [
        'service_id', 'payment_date', 'amount', 'payment_method', 'reference_number', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'float'];
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
