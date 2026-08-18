<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Receipt extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'invoice_id', 'number', 'client_name', 'client_contact',
        'description', 'amount', 'payment_method', 'issued_date', 'reference_no',
        'balance', 'created_by',
    ];

    protected function casts(): array
    {
        return ['issued_date' => 'date', 'amount' => 'float'];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function recipientEmail(): ?string
    {
        if (filter_var((string) $this->client_contact, FILTER_VALIDATE_EMAIL)) {
            return $this->client_contact;
        }

        return $this->invoice?->recipientEmail();
    }

    public function shortNumber(): string
    {
        return substr((string) $this->number, -4);
    }

    public function methodLabel(): string
    {
        return match ($this->payment_method) {
            'cash' => 'Cash',
            'mtn_momo' => 'MTN Mobile Money',
            'airtel_money' => 'Airtel Money',
            'bank' => 'Bank',
            default => $this->payment_method ?: 'Cash',
        };
    }
}
