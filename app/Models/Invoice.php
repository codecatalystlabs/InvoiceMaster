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
        'total', 'amount_paid', 'status', 'notes', 'created_by',
        'is_recurring', 'recurrence_frequency', 'next_recurrence_date', 'recurrence_parent_id',
        'pay_token', 'project_id', 'service_id',
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
            'amount_paid' => 'float',
            'is_recurring' => 'boolean',
            'next_recurrence_date' => 'date',
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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function outstanding(): float
    {
        return max(0, (float) $this->total - (float) $this->amount_paid);
    }

    public function payUrl(): string
    {
        if (! $this->pay_token) {
            $this->pay_token = \Illuminate\Support\Str::random(48);
            $this->saveQuietly();
        }

        return url('pay/'.$this->pay_token);
    }

    public function isOpen(): bool
    {
        return ! in_array(strtolower((string) $this->status), ['paid', 'cancelled', 'canceled'], true);
    }
}
