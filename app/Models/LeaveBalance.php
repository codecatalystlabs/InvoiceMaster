<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id', 'employee_id', 'leave_type_id', 'year', 'entitled', 'taken',
    ];

    protected function casts(): array
    {
        return [
            'entitled' => 'float',
            'taken' => 'float',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class, 'leave_type_id');
    }

    public function remaining(): float
    {
        return max(0, (float) $this->entitled - (float) $this->taken);
    }
}
