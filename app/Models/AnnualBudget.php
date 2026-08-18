<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnnualBudget extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'department_id', 'year', 'title', 'amount', 'status',
        'notes', 'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'approved_at' => 'datetime',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(BudgetAllocation::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function allocatedTotal(): float
    {
        return (float) $this->allocations()->sum('amount');
    }

    public function remainingToAllocate(): float
    {
        return max(0, (float) $this->amount - $this->allocatedTotal());
    }
}
