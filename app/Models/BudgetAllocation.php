<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetAllocation extends Model
{
    protected $fillable = [
        'annual_budget_id', 'name', 'category', 'amount', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'float'];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(AnnualBudget::class, 'annual_budget_id');
    }

    public function requisitions(): HasMany
    {
        return $this->hasMany(Requisition::class);
    }

    public static function categories(): array
    {
        return [
            'petty_cash' => 'Petty cash',
            'canteen' => 'Canteen',
            'operations' => 'Operations',
            'travel' => 'Travel',
            'utilities' => 'Utilities',
            'supplies' => 'Supplies',
            'other' => 'Other',
        ];
    }

    public function spent(): float
    {
        return (float) $this->requisitions()->where('status', 'closed')->sum('accounted_amount');
    }

    public function committed(): float
    {
        return (float) $this->requisitions()->whereNotIn('status', ['rejected', 'closed'])->sum('amount');
    }

    public function available(): float
    {
        return max(0, (float) $this->amount - $this->spent() - $this->committed());
    }
}
