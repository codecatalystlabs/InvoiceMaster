<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanteenMonthClose extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'year', 'month', 'expense_id', 'total', 'meal_count', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'float',
            'closed_at' => 'datetime',
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function label(): string
    {
        return now()->setDate((int) $this->year, (int) $this->month, 1)->format('F Y');
    }
}
