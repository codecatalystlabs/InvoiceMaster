<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'quotation_id', 'client_id', 'invoice_number', 'client_name',
        'client_contact', 'date', 'due_date', 'subtotal', 'tax', 'discount',
        'total', 'status', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'subtotal' => 'float',
            'tax' => 'float',
            'discount' => 'float',
            'total' => 'float',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(Receipt::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function displayClient(): string
    {
        return $this->client?->name ?: ($this->client_name ?: 'Walk-in');
    }

    public function recipientEmail(): ?string
    {
        if ($this->client?->email) {
            return $this->client->email;
        }
        if ($this->client_contact && filter_var($this->client_contact, FILTER_VALIDATE_EMAIL)) {
            return $this->client_contact;
        }

        return null;
    }
}
