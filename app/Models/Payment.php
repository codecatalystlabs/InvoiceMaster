<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'invoice_id', 'receipt_id', 'number', 'amount', 'method',
        'phone', 'reference', 'status', 'provider', 'provider_ref', 'paid_at',
        'meta', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float', 'paid_at' => 'datetime', 'meta' => 'array'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'cash' => 'Cash',
            'mtn_momo' => 'MTN Mobile Money',
            'airtel_money' => 'Airtel Money',
            'bank' => 'Bank',
            'card' => 'Card',
            default => $this->method ?: 'Payment',
        };
    }
}
