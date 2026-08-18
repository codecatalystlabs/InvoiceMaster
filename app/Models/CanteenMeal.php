<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CanteenMeal extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'user_id', 'meal_date', 'status', 'total', 'did_not_eat',
        'notes', 'submitted_at', 'reviewed_by', 'reviewed_at', 'review_notes', 'expense_id',
    ];

    protected function casts(): array
    {
        return [
            'meal_date' => 'date',
            'total' => 'float',
            'did_not_eat' => 'boolean',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(CanteenMealLine::class);
    }

    public function changeRequests(): HasMany
    {
        return $this->hasMany(ChangeRequest::class, 'entity_id')
            ->where('entity_type', 'CanteenMeal');
    }

    public function isLocked(): bool
    {
        return in_array($this->status, ['pending', 'approved', 'posted'], true);
    }

    public function canResubmit(): bool
    {
        return in_array($this->status, ['refused'], true);
    }

    public function snapshot(): array
    {
        return [
            'status' => $this->status,
            'total' => $this->total,
            'did_not_eat' => $this->did_not_eat,
            'notes' => $this->notes,
            'lines' => $this->lines->map(fn ($line) => [
                'canteen_item_id' => $line->canteen_item_id,
                'item_name' => $line->item_name,
                'item_type' => $line->item_type,
                'qty' => $line->qty,
                'unit_price' => $line->unit_price,
                'line_total' => $line->line_total,
            ])->all(),
        ];
    }
}
